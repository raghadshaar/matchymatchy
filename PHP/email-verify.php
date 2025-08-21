<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false]); exit;
}

$email = trim((string)($_POST['email'] ?? ''));
$code  = (string)($_POST['code'] ?? '');   // keep as string to preserve leading zeros

// Validate inputs server-side (OWASP: validate on trusted system)
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $code)) {
    http_response_code(422);
    echo json_encode(['ok'=>false, 'error'=>'Invalid input']); exit;
}

$conn = db();

/* Use DB time for expiration and block re-use after success */
$stmt = $conn->prepare("
  SELECT id, code_hash, token, attempt_count
  FROM email_verifications
  WHERE email = ?
    AND verified_at IS NULL
    AND expires_at > NOW()
  LIMIT 1
");
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'Invalid or expired code']); exit;
}

/* Rate-limit attempts */
if ((int)$row['attempt_count'] >= 5) {
    http_response_code(429);
    echo json_encode(['ok'=>false, 'error'=>'Too many attempts']); exit;
}

/* Verify the 6-digit code against the stored hash */
if (!password_verify($code, $row['code_hash'])) {
    $stmt = $conn->prepare("UPDATE email_verifications SET attempt_count = attempt_count + 1 WHERE id = ?");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $stmt->close();

    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'Invalid or expired code']); exit;
}

/* Success: mark verified, optionally reset attempt_count */
$stmt = $conn->prepare("UPDATE email_verifications SET verified_at = NOW(), attempt_count = 0 WHERE id = ?");
$stmt->bind_param('i', $row['id']);
$stmt->execute();
$stmt->close();

/* Return the server token that was issued during "send" */
echo json_encode(['ok'=>true, 'verification_token'=>$row['token']]);
