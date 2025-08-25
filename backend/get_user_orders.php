<?php
// /matchymatchy/backend/get_user_orders.php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost',
    'http://127.0.0.1',
    // 'https://your-domain.com',
];
if ($origin && in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
/* ===== نهاية CORS ===== */

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'pdo_init_failed']);
        exit;
    }

    // ==== حدد الإيميل من السيشن ====
    $email = null;

    // 1) لو عندك user_id بالسيشن
    if (!empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $email = $stmt->fetchColumn() ?: null;
        if ($email) {
            $email = trim((string)$email);
        }
    }

    // 2) إن ما توفر user_id أو ما لقينا إيميل، جرّبي user_email من السيشن
    if (!$email && !empty($_SESSION['user_email'])) {
        $email = trim((string)$_SESSION['user_email']);
    }

    // 3) ما في إيميل => غير مسجل دخول
    if (!$email) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        exit;
    }

    // Pagination (ثابت وآمن)
    $limit  = isset($_GET['limit'])  ? max(1, min(200, (int)$_GET['limit'])) : 100;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

    // خليه أرقام صافية وادخله مباشرة لتجنّب مشاكل bind مع LIMIT
    $limitSql  = (string)$limit;
    $offsetSql = (string)$offset;

    // === اجلب الطلبات حسب بريد المستخدم ===
    $sql = "
        SELECT
            o.id,
            o.public_id,
            o.order_date,
            o.customer_name,
            o.customer_email,
            o.customer_phone,
            o.customer_address,
            o.subtotal,
            o.shipping,
            o.tax,
            o.total,
            o.payment_status,
            o.order_status
        FROM orders o
        WHERE o.customer_email = ?
        ORDER BY COALESCE(o.order_date, o.id * 1) DESC, o.id DESC
        LIMIT $limitSql OFFSET $offsetSql
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // عناصر كل طلب
    $itemStmt = $pdo->prepare("
        SELECT product_id, product_name, unit_price, quantity, line_total
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
    ");

    foreach ($orders as &$order) {
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $ts = !empty($order['order_date']) ? strtotime((string)$order['order_date']) : false;
        $order['date'] = $ts ? date('F j, Y, g:i a', $ts) : '';
    }
    unset($order);

    echo json_encode([
        'ok'      => true,
        'orders'  => $orders,
        'limit'   => $limit,
        'offset'  => $offset
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'server_error',
        'detail'=> $e->getMessage()
    ]);
}
