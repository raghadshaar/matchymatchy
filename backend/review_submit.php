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
$data = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($data['product_id'] ?? 0);
$user_id = (int)($data['user_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$comment = trim($data['comment'] ?? '');

if (!$product_id || !$user_id || !$rating || $comment === '') {
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at)
VALUES (?, ?, ?, ?, NOW())
ON DUPLICATE KEY UPDATE 
  rating=VALUES(rating),
  comment=VALUES(comment),
  created_at=NOW()
");

    $stmt->execute([$product_id, $user_id, $rating, $comment]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
