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

// استخراج البيانات من JSON
$product_id  = (int)($data['product_id'] ?? 0);
$user_email  = trim($data['user_email'] ?? '');
$rating      = (int)($data['rating'] ?? 0);
$comment     = trim($data['comment'] ?? '');

// التحقق من القيم المطلوبة
if (!$product_id || !$rating || $comment === '' || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

// جلب user_id من الإيميل
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

$user_id = (int)$user['id'];

try {
    $stmt = $pdo->prepare("
        INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            rating = VALUES(rating),
            comment = VALUES(comment),
            created_at = NOW()
    ");
    $stmt->execute([$product_id, $user_id, $rating, $comment]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
