<?php
// /matchymatchy/api/paypal/capture-order.php
require __DIR__.'/config.php';
require __DIR__.'/../db.php';

header('Content-Type: application/json; charset=utf-8');
$orderID = $_GET['orderID'] ?? '';
if (!$orderID) { http_response_code(400); echo json_encode(['error'=>'orderID missing']); exit; }

// token
$ch = curl_init(PP_API . '/v1/oauth2/token');
curl_setopt_array($ch,[
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
    CURLOPT_USERPWD => PP_CLIENT_ID . ':' . PP_SECRET,
    CURLOPT_RETURNTRANSFER => true
]);
$tok = json_decode(curl_exec($ch), true);
$access = $tok['access_token'] ?? null;

// capture
$ch = curl_init(PP_API . "/v2/checkout/orders/{$orderID}/capture");
curl_setopt_array($ch,[
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer '.$access],
    CURLOPT_RETURNTRANSFER => true
]);
$res = curl_exec($ch);
$data = json_decode($res, true);

// حدّثي الطلب محليًا إلى CAPTURED إن نجح
if (($data['status'] ?? '') === 'COMPLETED') {
    // TODO: update orders set status='CAPTURED' where paypal_order_id=...
}

echo $res;

