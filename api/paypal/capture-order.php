<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// لا تظهري أخطاء HTML
ini_set('display_errors','0'); ini_set('log_errors','1'); error_reporting(E_ALL);
while (ob_get_level()) { ob_end_clean(); }
set_error_handler(function($s,$m,$f,$l){ throw new ErrorException($m,0,$s,$f,$l); });
register_shutdown_function(function(){
    $e=error_get_last();
    if($e && in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true)){
        http_response_code(500); echo json_encode(['ok'=>false,'error'=>'fatal','details'=>$e['message']]);
    }
});

require_once __DIR__ . '/config.php';      // يعرّف PP_API و PP_CLIENT_ID/PP_SECRET ويستدعي config العام
require_once __DIR__ . '/../../PHP/db.php';// يجب أن يعرّف $pdo مع ERRMODE_EXCEPTION

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$userId = $_SESSION['user_id'] ?? null; // سكيمتك تسمح NULL

$orderID = $_GET['orderID'] ?? '';
if ($orderID === '') { echo json_encode(['ok'=>false,'error'=>'missing_order_id']); exit; }

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
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($resp === false) throw new RuntimeException('PayPal capture error');
    $cap = json_decode($resp, true);
    if ($code !== 201) throw new RuntimeException('paypal_capture_failed: ' . $resp);
    return $cap;
}

function make_public_id(int $numericId): string {
    return 'ORD-' . date('Y') . '-' . str_pad((string)$numericId, 6, '0', STR_PAD_LEFT);
}

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) throw new RuntimeException('PDO not initialized');

    // 1) Capture at PayPal
    $token = get_token();
    $cap   = capture_paypal_order($orderID, $token);
    $status = $cap['status'] ?? '';

    if ($status !== 'COMPLETED') {
        echo json_encode(['ok'=>false,'status'=>$status,'details'=>$cap]); // اظهري الحالة ليسهل التشخيص
        exit;
    }

    // PayPal amounts (أول PU وأول capture)
    $pu        = $cap['purchase_units'][0] ?? [];
    $capture   = $pu['payments']['captures'][0] ?? [];
    $ppAmount  = isset($capture['amount']['value']) ? (float)$capture['amount']['value'] : 0.0;
    $currency  = $capture['amount']['currency_code'] ?? 'ILS';

    // بيانات العميل من PayPal (قد تختلف حسب نوع الدفع)
    $payer = $cap['payer'] ?? [];
    $customerEmail = $payer['email_address'] ?? 'unknown@example.com';
    $given = $payer['name']['given_name'] ?? '';
    $sur   = $payer['name']['surname'] ?? '';
    $customerName = trim($given . ' ' . $sur) ?: 'Guest';
    $customerPhone = $payer['phone']['phone_number']['national_number'] ?? null;

    // العنوان إن وُجد
    $addr = $pu['shipping']['address'] ?? null;
    $customerAddress = null;
    if ($addr) {
        $parts = [
            $addr['address_line_1'] ?? null,
            $addr['address_line_2'] ?? null,
            $addr['admin_area_2'] ?? null,
            $addr['admin_area_1'] ?? null,
            $addr['postal_code'] ?? null,
            $addr['country_code'] ?? null,
        ];
        $customerAddress = implode(', ', array_filter($parts));
    }

    // 2) اقرأ السلة من DB واحسب totals بما يوافق سكيمتك
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
    if (!$items) { throw new RuntimeException('cart_empty_on_capture'); }

    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += round((float)$it['unit_price'] * (int)$it['quantity'], 2);
    }
    $shipping = 15.00;       // عدّلي حسب قواعدك
    $vatRate  = 0.17;        // 17% VAT
    $tax      = round($subtotal * $vatRate, 2);
    $total    = round($subtotal + $shipping + $tax, 2);

    // (اختياري) تحقّق أن مبلغ PayPal يساوي total النهائي
    if (abs($ppAmount - $total) > 0.01) {
        // هذا يعني إنه create-order في السيرفر ما استخدم نفس الحساب — راجعي create-order.php
        // نكمل الحفظ بـ total المحسوب محليًا لأنه هو مرجعنا للسجلات
    }

    // 3) أدخلي الطلب + العناصر داخل Transaction بما يتوافق مع سكيمتك
    $pdo->beginTransaction();

    // أولاً ندخل order بس public_id مؤقت فاضي لنحصل على id
    $insOrder = $pdo->prepare("
    INSERT INTO orders
      (public_id, user_id, customer_name, customer_email, customer_phone, customer_address,
       subtotal, shipping, tax, total, payment_status, order_status)
    VALUES
      ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', 'Processing')
  ");
    $insOrder->execute([
        $userId,
        $customerName,
        $customerEmail,
        $customerPhone,
        $customerAddress,
        $subtotal,
        $shipping,
        $tax,
        $total
    ]);
    $orderDbId = (int)$pdo->lastInsertId();

    // حدّث public_id بالشكل المطلوب ORD-YYYY-000123
    $publicId = make_public_id($orderDbId);
    $pdo->prepare("UPDATE orders SET public_id=? WHERE id=?")->execute([$publicId, $orderDbId]);

    // عناصر الطلب
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
            $it['product_id'],
            $it['product_name'] ?? 'Product',
            (string)($it['size'] ?? ''),
            (float)$it['unit_price'],
            (int)$it['quantity'],
            $lineTotal
        ]);
    }

    // أفرغي السلة
    $cartId = (int)($items[0]['cart_id'] ?? 0);
    if ($cartId) {
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cartId]);
        $pdo->prepare("DELETE FROM carts      WHERE id=?")->execute([$cartId]);
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'status' => $status,
        'order_id' => $orderDbId,
        'public_id' => $publicId,
        'currency' => $currency,
        'total' => $total
    ]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('[capture-order.php] '.$e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'DB insert failed','details'=>$e->getMessage()]);
}
