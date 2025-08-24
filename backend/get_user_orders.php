<?php
require_once __DIR__ . '/../PHP/db.php';
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['ok' => false, 'error' => 'Missing user_id']);
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized']);
    exit;
}

// Fetch user orders
$stmt = $pdo->prepare("SELECT id, public_id, total, order_date FROM orders WHERE id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products for each order
foreach ($orders as &$order) {
    $stmt = $pdo->prepare("
        SELECT product_name, unit_price, quantity, line_total
        FROM order_items
        WHERE order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $order['date'] = date('F j, Y', strtotime($order['order_date']));
}

echo json_encode(['ok' => true, 'orders' => $orders]);
