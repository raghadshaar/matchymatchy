<?php
require __DIR__ . '/../PHP/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false]); exit; }

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }

$conn = db();
// Require an existing account
$stmt = $conn->prepare("SELECT id, email FROM users WHERE email=? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'no_account']); exit; }

// create one-time token
$token = bin2hex(random_bytes(32)); // 64-hex
$exp   = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO magic_links (user_id, token, purpose, expires_at) VALUES (?,?, 'reset_password', ?)");
$stmt->bind_param('iss', $user['id'], $token, $exp);
$stmt->execute();
$stmt->close();

// send link to sign-in page (inline reset UI reads ?token=)
$link = 'http://localhost/matchymatchy/sign-in.php?token=' . urlencode($token);
$html = "<p>Click to reset your password: <a href=\"{$link}\">Reset Password</a></p><p>This link expires in 30 minutes.</p>";
$text = "Reset your password: {$link}\nThis link expires in 30 minutes.";

try { sendEmail($user['email'], 'Reset your Matchy Matchy password', $html, $text); } catch (Throwable $e) { /* not fatal */ }

echo json_encode(['ok'=>true]);
