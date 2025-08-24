<?php
// /backend/get_user_orders.php
require_once __DIR__ . '/../PHP/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'PDO not initialized']);
        exit;
    }

    // إما user_id أو email (يفضّل واحد منهم)
    $user_id = isset($_GET['user_id']) ? trim((string)$_GET['user_id']) : null;
    $email   = isset($_GET['email'])   ? trim((string)$_GET['email'])   : null;

    if ($email === '' && $user_id === '') $user_id = null;
    if ($email === '') $email = null;

    if (!$email && !$user_id) {
        echo json_encode(['ok' => false, 'error' => 'Missing user_id or email']);
        exit;
    }

    // لو عندي user_id فقط، استخرج الإيميل من users
    if (!$email && $user_id) {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['email'])) {
            echo json_encode(['ok' => false, 'error' => 'User not found or email missing']);
            exit;
        }
        $email = $row['email'];
    }

    // اجلب الطلبات حسب البريد
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
        ORDER BY o.order_date DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // عناصر كل طلب
    $itemSql = "
        SELECT product_id, product_name, unit_price, quantity, line_total
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
    ";
    $itemStmt = $pdo->prepare($itemSql);

    foreach ($orders as &$order) {
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $order['date'] = date('F j, Y', strtotime($order['order_date'] ?? 'now'));
    }
    unset($order);

    echo json_encode(['ok' => true, 'orders' => $orders], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error', 'detail' => $e->getMessage()]);
}
