<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// -------- Hard JSON-only error surface ----------
ini_set('display_errors','0'); ini_set('log_errors','1'); error_reporting(E_ALL);
while (ob_get_level()) { ob_end_clean(); }
set_error_handler(function($s,$m,$f,$l){ throw new ErrorException($m,0,$s,$f,$l); });
register_shutdown_function(function(){
    $e=error_get_last();
    if($e && in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true)){
        http_response_code(500); echo json_encode(['ok'=>false,'error'=>'fatal','details'=>$e['message']]);
    }
});

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../PHP/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$userId  = $_SESSION['user_id'] ?? null;
$orderID = $_GET['orderID']     ?? '';

if (!$userId)          { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
if ($orderID === '')   { echo json_encode(['ok'=>false,'error'=>'missing_order_id']); exit; }

// -------- Helpers --------
function get_token(): string {
    $ch = curl_init(PP_API . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json','Accept-Language: en_US'],
        CURLOPT_USERPWD => PP_CLIENT_ID . ':' . PP_SECRET,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($resp === false || $code !== 200) {
        $err = $resp === false ? curl_error($ch) : $resp;
        curl_close($ch);
        throw new RuntimeException('PayPal token failed: ' . $err);
    }
    curl_close($ch);
    $json = json_decode($resp, true);
    return $json['access_token'] ?? '';
}

function capture_paypal_order(string $orderId, string $token): array {
    $ch = curl_init(PP_API . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            // Idempotency: safe retry for POST capture
            'PayPal-Request-Id: ' . $orderId
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($resp === false) { $err = curl_error($ch); curl_close($ch); throw new RuntimeException('PayPal capture error: '.$err); }
    curl_close($ch);
    $cap = json_decode($resp, true);
    if ($code !== 201 && $code !== 200) {
        throw new RuntimeException('paypal_capture_failed: HTTP '.$code.'; body='.$resp);
    }
    return $cap;
}

function make_public_id(int $numericId): string {
    return 'ORD-' . date('Y') . '-' . str_pad((string)$numericId, 6, '0', STR_PAD_LEFT);
}

function load_coupon(PDO $pdo, string $code): ?array {
    $st = $pdo->prepare("
        SELECT code, type, amount, min_subtotal, starts_at, ends_at, active, max_uses, used_count
        FROM coupons
        WHERE UPPER(TRIM(code)) = UPPER(TRIM(?))
          AND active=1
          AND (starts_at IS NULL OR starts_at <= NOW())
          AND (ends_at   IS NULL OR ends_at   >= NOW())
        LIMIT 1
    ");
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) throw new RuntimeException('PDO not initialized');

    // 1) Capture at PayPal
    $token   = get_token();
    $cap     = capture_paypal_order($orderID, $token);
    $status  = $cap['status'] ?? '';
    $pu      = $cap['purchase_units'][0] ?? [];
    $capture = $pu['payments']['captures'][0] ?? [];

    $ppAmount    = isset($capture['amount']['value']) ? (float)$capture['amount']['value'] : 0.0;
    $ppCurrency  = $capture['amount']['currency_code'] ?? '';
    $ppCaptureId = $capture['id'] ?? null;
    $ppOrderId   = $cap['id']   ?? $orderID;
    $payer       = $cap['payer'] ?? [];

    if ($status !== 'COMPLETED' || $ppCurrency !== 'ILS' || !$ppCaptureId) {
        echo json_encode(['ok'=>false,'error'=>'paypal_not_completed_or_bad_currency_or_missing_capture','details'=>$cap]);
        exit;
    }

    // Idempotent DB guard
    $dupe = $pdo->prepare("SELECT id, public_id, total, currency_code FROM orders WHERE paypal_capture_id = ? LIMIT 1");
    $dupe->execute([$ppCaptureId]);
    if ($row = $dupe->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'ok'        => true,
            'status'    => $status,
            'order_id'  => (int)$row['id'],
            'public_id' => $row['public_id'],
            'currency'  => $row['currency_code'],
            'total'     => (float)$row['total'],
            'idempotent'=> true
        ]);
        exit;
    }

    // Payer details
    $customerEmail = $payer['email_address'] ?? 'unknown@example.com';
    $given         = $payer['name']['given_name'] ?? '';
    $sur           = $payer['name']['surname'] ?? '';
    $customerName  = trim($given . ' ' . $sur) ?: 'Guest';
    $customerPhone = $payer['phone']['phone_number']['national_number'] ?? null;

    $addr = $pu['shipping']['address'] ?? null;
    $customerAddress = null;
    if ($addr) {
        $parts = [
            $addr['address_line_1'] ?? null,
            $addr['address_line_2'] ?? null,
            $addr['admin_area_2']   ?? null,
            $addr['admin_area_1']   ?? null,
            $addr['postal_code']    ?? null,
            $addr['country_code']   ?? null,
        ];
        $customerAddress = implode(', ', array_filter($parts));
    }

    // 2) Read cart and compute totals EXACTLY like create-order
    $stmt = $pdo->prepare("
        SELECT c.id AS cart_id, ci.product_id, ci.size, ci.quantity, ci.unit_price,
               p.name AS product_name
        FROM carts c
        JOIN cart_items ci ON ci.cart_id = c.id
        JOIN products p    ON p.id = ci.product_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$items) throw new RuntimeException('cart_empty_on_capture');

    // item_total (per-line 2dp, then sum)
    $itemTotal = 0.0;
    foreach ($items as $it) {
        $itemTotal += round((float)$it['unit_price'] * (int)$it['quantity'], 2);
    }
    $itemTotal = round($itemTotal, 2);
    $subtotal  = $itemTotal;  // store pre-discount in orders.subtotal

    // read saved coupon
    $cartRow = $pdo->prepare("SELECT id, coupon_code FROM carts WHERE user_id=? LIMIT 1");
    $cartRow->execute([$userId]);
    $cartMeta   = $cartRow->fetch(PDO::FETCH_ASSOC) ?: ['id'=>null,'coupon_code'=>null];
    $cartId     = (int)($cartMeta['id'] ?? 0);
    $couponCode = $cartMeta['coupon_code'] ?? null;

    // discount (same rules as create-order)
    $discount = 0.0;
    if ($couponCode) {
        $c = load_coupon($pdo, $couponCode);
        if ($c && $itemTotal >= (float)$c['min_subtotal']) {
            if ($c['type'] === 'percentage') $discount = round($itemTotal * (float)$c['amount'], 2);
            else                              $discount = round((float)$c['amount'], 2);
            $discount = min($discount, $itemTotal);
        }
    }

    $shipping = 15.00;
    $vatRate  = 0.17;
    $taxBase  = max(0.0, $itemTotal - $discount);
    $tax      = round($taxBase * $vatRate, 2);
    $total    = round($taxBase + $shipping + $tax, 2);

    // Must match the captured amount from PayPal
    if (abs($ppAmount - $total) > 0.01) {
        echo json_encode([
            'ok'=>false,'error'=>'amount_mismatch','paypal'=>$ppAmount,'local'=>$total,
            'dbg'=>['item_total'=>$itemTotal,'discount'=>$discount,'tax'=>$tax,'shipping'=>$shipping,'tax_base'=>$taxBase]
        ]);
        exit;
    }

    // 3) Write order atomically (and consume coupon if applicable)
    $pdo->beginTransaction();

    // Optional: consume one coupon usage (guard exhaustion)
    if ($couponCode && $discount > 0.0) {
        $u = $pdo->prepare("
            UPDATE coupons
            SET used_count = used_count + 1
            WHERE code=? AND active=1
              AND (max_uses IS NULL OR used_count < max_uses)
        ");
        $u->execute([$couponCode]);
        if ($u->rowCount() === 0) {
            // exhausted/inactive between create and capture
            throw new RuntimeException('coupon_exhausted_or_inactive');
        }
    }

    // Insert order
    $tmpPublic = 'PEND-'.strtoupper(bin2hex(random_bytes(6)));
    $insOrder = $pdo->prepare("
        INSERT INTO orders
          (public_id, user_id, order_date,
           customer_name, customer_email, customer_phone, customer_address,
           subtotal, shipping, tax, total,
           payment_status, order_status,
           currency_code, discount, coupon_code,
           paypal_order_id, paypal_capture_id, paypal_status, paypal_payer_email, paypal_raw)
        VALUES
          (?, ?, NOW(),
           ?, ?, ?, ?,
           ?, ?, ?, ?,
           'Paid', 'Processing',
           ?, ?, ?,
           ?, ?, ?, ?, ?)
    ");
    $insOrder->execute([
        $tmpPublic,
        $userId,
        $customerName, $customerEmail, $customerPhone, $customerAddress,
        $subtotal, $shipping, $tax, $total,
        $ppCurrency, $discount, $couponCode,
        $ppOrderId, $ppCaptureId, $status, ($payer['email_address'] ?? null),
        json_encode($cap, JSON_UNESCAPED_UNICODE)
    ]);

    $orderDbId = (int)$pdo->lastInsertId();
    $publicId  = make_public_id($orderDbId);
    $pdo->prepare("UPDATE orders SET public_id=? WHERE id=?")->execute([$publicId, $orderDbId]);

    // Insert items
    $insItem = $pdo->prepare("
        INSERT INTO order_items
          (order_id, product_id, product_name, size, unit_price, quantity, line_total)
        VALUES
          (?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($items as $it) {
        $lineTotal = round((float)$it['unit_price'] * (int)$it['quantity'], 2);
        $insItem->execute([
            $orderDbId,
            $it['product_id'] ?? null,
            $it['product_name'] ?? 'Product',
            (string)($it['size'] ?? ''),
            (float)$it['unit_price'],
            (int)$it['quantity'],
            $lineTotal
        ]);
    }

    // Clear cart
    if ($cartId) {
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cartId]);
        $pdo->prepare("DELETE FROM carts      WHERE id=?")->execute([$cartId]);
    }

    $pdo->commit();

    echo json_encode([
        'ok'        => true,
        'status'    => $status,
        'order_id'  => $orderDbId,
        'public_id' => $publicId,
        'currency'  => $ppCurrency,
        'total'     => $total
    ]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('[capture-order.php] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'DB insert failed','details'=>$e->getMessage()]);
}
