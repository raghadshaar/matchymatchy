<?php
// /matchymatchy/api/paypal/config.php
// ملاحظة مهمة: لا تطبعي أي شيء هنا (لا echo ولا HTML)

// نستورد إعدادات المشروع العامة (اللي فيها DB + session_start)
require __DIR__ . '/../../PHP/config.php';  // عدّلي المسار إذا كان مختلف عندك

// ========= PayPal (SANDBOX) =========
const SHOP_CURRENCY = 'ILS';
const PP_API        = 'https://api-m.sandbox.paypal.com';

// عدّلي هذول من PayPal Developer > Sandbox > App تبعك (Matchy Matchy)
const PP_CLIENT_ID  = 'AeNRbUtyWFnhdgD_OhFSyJJlz1qg8M3pVz54kkxwK97qK8YyBJTRTL4_1JMImxb8TTvRDqHp3kYVCcW2';
const PP_SECRET     = 'ELwYnn4u8KzHLMZFWGNkGO6TiXKDvBRxFWu1nLVupTamMWmU_7aAg64UKMs0anJOEBbcldo7IIhS2XE8';

// دوال مساعدة اختيارية للكارت (لو حابة تستخدميها)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function cart_set_total(float $total): void {
    $_SESSION['cart_total'] = $total;
}
function cart_get_total(): float {
    return (float)($_SESSION['cart_total'] ?? 0.0);
}


// /matchymatchy/api/paypal/config.php
// ... your current consts ...

// Alias to names your endpoints expect:
if (!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', PP_CLIENT_ID);
if (!defined('PAYPAL_SECRET'))    define('PAYPAL_SECRET',    PP_SECRET);

// Optional: infer env for any helper using it
if (!defined('PAYPAL_ENV')) {
    define('PAYPAL_ENV', str_contains(PP_API, 'sandbox') ? 'sandbox' : 'live');
}

