<?php
// C:\xampp\htdocs\matchymatchy\api\check-email.php
require __DIR__ . '/../PHP/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false]); exit;
}

$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok'=>false, 'error'=>'Invalid email']); exit;
}

$conn = db();

// normalize domain case
if (strpos($email,'@') !== false) {
    [$local,$domain] = explode('@', $email, 2);
    $email = $local . '@' . mb_strtolower($domain);
}

$stmt = $conn->prepare('SELECT provider FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    echo json_encode(['ok'=>true, 'exists'=>true, 'provider'=>$row['provider']]); // 'local' or 'google'
} else {
    echo json_encode(['ok'=>true, 'exists'=>false]);
}
