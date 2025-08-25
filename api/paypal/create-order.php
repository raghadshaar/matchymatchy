<?php
// /matchymatchy/api/paypal/create-order.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config.php';
require_once __DIR__ . '/../../PHP/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }

// ---- helpers ----
function pp_base(): string {
    $env = defined('PAYPAL_ENV') ? PAYPAL_ENV : 'sandbox';
    return $env === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}
function get_token(): string {
    $ch = curl_init(pp_base().'/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json','Accept-Language: en_US'],
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) throw new RuntimeException('PayPal token error: '.curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $json = json_decode($resp, true);
    if ($code !== 200 || empty($json['access_token'])) {
        throw new RuntimeException('PayPal token failed: HTTP '.$code.'; body='.$resp);
    }
    return $json['access_token'];
}

// ⚠️ ADD THIS: coupon loader (same logic you used server-side elsewhere)
function load_coupon(PDO $pdo, string $code): ?array {
    $st = $pdo->prepare("
        SELECT code, type, amount, min_subtotal, starts_at, ends_at, active, max_uses, used_count
        FROM coupons
        WHERE code=? AND active=1
          AND (starts_at IS NULL OR starts_at <= NOW())
          AND (ends_at   IS NULL OR ends_at   >= NOW())
        LIMIT 1
    ");
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('PDO not initialized');
    }

    // fetch cart + items
    $stmt = $pdo->prepare("
        SELECT ci.id, ci.product_id, ci.size, ci.quantity, ci.unit_price,
               p.name, p.image_main_url AS image
        FROM carts c
        JOIN cart_items ci ON ci.cart_id = c.id
        JOIN products p ON p.id = ci.product_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        http_response_code(200);
        echo json_encode(['ok'=>false,'error'=>'empty_cart']);
        exit;
    }

    // ⚠️ READ coupon only once
    $cartRow = $pdo->prepare("SELECT id, coupon_code FROM carts WHERE user_id=? LIMIT 1");
    $cartRow->execute([$userId]);
    $cart = $cartRow->fetch(PDO::FETCH_ASSOC) ?: ['id'=>null,'coupon_code'=>null];
    $cartId = (int)($cart['id'] ?? 0);
    $couponCode = $cart['coupon_code'] ?? null;

    // Compute itemTotal using per-line rounding (align with capture)
    $itemTotal = 0.0;
    foreach ($items as $it) {
        $itemTotal += round((float)$it['unit_price'] * (int)$it['quantity'], 2);
    }
    $itemTotal = round($itemTotal, 2);

    // ⚠️ Apply coupon (same formula you will use in capture)
    $discount = 0.0;
    if ($couponCode) {
        $c = load_coupon($pdo, $couponCode);
        if ($c && $itemTotal >= (float)$c['min_subtotal']) {
            if ($c['type'] === 'percentage') $discount = round($itemTotal * (float)$c['amount'], 2); // amount is fraction (e.g., 0.10)
            else                              $discount = round((float)$c['amount'], 2);             // fixed currency amount
            $discount = min($discount, $itemTotal);
        }
    }

    $shipping = 15.00;
    $vatRate  = 0.17;
    $taxBase  = max(0.0, $itemTotal - $discount);
    $tax      = round($taxBase * $vatRate, 2);
    $total    = round($taxBase + $shipping + $tax, 2);

    // ⚠️ REMOVE this from create-order (ppAmount only exists after capture)
    // if (abs($ppAmount - $total) > 0.01) { ... }

    // Build PayPal order with discount in breakdown
    $order = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'amount' => [
                'currency_code' => 'ILS',
                'value' => number_format($total, 2, '.', ''),
                'breakdown' => [
                    'item_total' => ['currency_code'=>'ILS','value'=>number_format($itemTotal, 2, '.', '')],
                    'shipping'   => ['currency_code'=>'ILS','value'=>number_format($shipping,   2, '.', '')],
                    'tax_total'  => ['currency_code'=>'ILS','value'=>number_format($tax,        2, '.', '')],
                    'discount'   => ['currency_code'=>'ILS','value'=>number_format($discount,   2, '.', '')],
                ]
            ],
            'items' => array_map(function($it){
                return [
                    'name'        => ($it['name'] ?? 'Product') ?: 'Product',
                    'quantity'    => (string)((int)$it['quantity']),
                    'unit_amount' => [
                        'currency_code'=>'ILS',
                        'value'=> number_format((float)$it['unit_price'], 2, '.', '')
                    ]
                ];
            }, $items),
        ]],
        'application_context' => [
            'user_action' => 'PAY_NOW',
            'shipping_preference' => 'NO_SHIPPING'
        ]
    ];

    $token = get_token();

    $ch = curl_init(pp_base().'/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
        ],
        CURLOPT_POSTFIELDS => json_encode($order),
        CURLOPT_RETURNTRANSFER => true
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) throw new RuntimeException('PayPal create error: '.curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $json = json_decode($resp, true);
    if ($code !== 201 || empty($json['id'])) {
        error_log('PP create-order failed: HTTP '.$code.'; body='.$resp);
        http_response_code(200);
        echo json_encode(['ok'=>false,'error'=>'paypal_create_failed','details'=>$json]);
        exit;
    }

    echo json_encode(['ok'=>true, 'id'=>$json['id']]);
} catch (Throwable $e) {
    error_log('[create-order.php] '.$e->getMessage());
    http_response_code(200);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
