<?php
// /matchymatchy/api/paypal/create-order.php
require __DIR__.'/config.php';   // فيه PP_CLIENT_ID, PP_SECRET, PP_API, SHOP_CURRENCY
require __DIR__.'/../db.php';    // اتصال mysqli + استرجاع السلة من Session/DB

header('Content-Type: application/json; charset=utf-8');

// 1) احسبي الإجمالي الحقيقي من السلة على السيرفر
session_start();
$total = /* TODO: اجمعي أسعار cart الخاصة بالمستخدم */  (float)($_SESSION['cart_total'] ?? 0.0);
if ($total <= 0) { http_response_code(400); echo json_encode(['error'=>'Empty cart']); exit; }

// 2) احصلي على access_token
$ch = curl_init(PP_API . '/v1/oauth2/token');
curl_setopt_array($ch,[
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
    CURLOPT_USERPWD => PP_CLIENT_ID . ':' . PP_SECRET,
    CURLOPT_RETURNTRANSFER => true
]);
$tok = json_decode(curl_exec($ch), true);
$access = $tok['access_token'] ?? null;
if (!$access) { http_response_code(500); echo json_encode(['error'=>'token']); exit; }

// 3) أنشئي Order
$body = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'amount' => [
            'currency_code' => SHOP_CURRENCY,          // ILS
            'value' => number_format($total, 2, '.', '')
        ]
    ]]
];

$ch = curl_init(PP_API . '/v2/checkout/orders');
curl_setopt_array($ch,[
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer '.$access],
    CURLOPT_POSTFIELDS => json_encode($body),
    CURLOPT_RETURNTRANSFER => true
]);
$res = curl_exec($ch);
$data = json_decode($res, true);

// احفظي order مبدئيًا عندك (اختياري)
echo json_encode(['id' => $data['id'] ?? null]);

