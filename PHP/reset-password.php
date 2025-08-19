<?php
// C:\xampp\htdocs\matchymatchy\PHP\reset-password.php
require __DIR__ . '/config.php';

$conn = db();

function bad($msg) {
    http_response_code(400);
    echo $msg;
    exit;
}

function valid_token_format($t) {
    return (bool)preg_match('/^[a-f0-9]{64}$/i', $t);
}

/* ----------------------------
   GET: verify token and bounce to the UI
   ---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';
    if (!valid_token_format($token)) {
        bad('Invalid link.');
    }

    // Check token exists / not expired / not used
    $stmt = $conn->prepare("
        SELECT id, expires_at, used_at
        FROM magic_links
        WHERE token = ? AND purpose = 'reset_password'
        LIMIT 1
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$row ||
        !empty($row['used_at']) ||
        new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable()
    ) {
        bad('Invalid or expired link.');
    }

    // Redirect into your front-end page that shows the reset form
    header('Location: /matchymatchy/sign-in.php?token=' . urlencode($token));
    exit;
}

/* ----------------------------
   POST: actually reset the password
   ---------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If someone hits it some other way, just bounce to sign-in
    header('Location: /matchymatchy/sign-in.php');
    exit;
}

$token = $_POST['token'] ?? '';
$p1    = $_POST['p1'] ?? '';
$p2    = $_POST['p2'] ?? '';

if (!valid_token_format($token)) {
    bad('Invalid link.');
}
if ($p1 !== $p2) {
    bad('Passwords do not match.');
}
$strong = strlen($p1) >= 8 && preg_match('/[A-Z]/',$p1) && preg_match('/[a-z]/',$p1) && preg_match('/\d/',$p1);
if (!$strong) {
    bad('Password is too weak.');
}

$stmt = $conn->prepare("
  SELECT id, user_id, expires_at, used_at
  FROM magic_links
  WHERE token = ? AND purpose = 'reset_password'
  LIMIT 1
");
$stmt->bind_param('s', $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$row ||
    !empty($row['used_at']) ||
    new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable()
) {
    bad('Invalid or expired link.');
}

$hash = password_hash($p1, PASSWORD_DEFAULT);
$uid  = (int)$row['user_id'];

$stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
$stmt->bind_param('si', $hash, $uid);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("UPDATE magic_links SET used_at = NOW() WHERE id = ?");
$stmt->bind_param('i', $row['id']);
$stmt->execute();
$stmt->close();

// Done — back to sign-in with a friendly message
header('Location: /matchymatchy/sign-in.php?notice=' . urlencode('Password updated. Please sign in.'));
exit;
