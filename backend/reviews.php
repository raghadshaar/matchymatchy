<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if (!$product_id) {
    echo json_encode(['ok' => false, 'error' => 'Missing product_id']);
    exit;
}

$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 5;
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

$stmt = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at, 
           u.first_name, u.last_name, u.email, u.avatar
    FROM product_reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.product_id = ?
      AND r.comment IS NOT NULL
      AND r.comment != ''
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $product_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);

$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'reviews' => $reviews]);
