<?php
// C:\xampp\htdocs\matchymatchy\api\login.php
require __DIR__ . '/../PHP/config.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false]); exit;
}

$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

// basic validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Invalid credentials']); exit;
}

// normalize domain case (example@GMAIL.COM -> example@gmail.com)
if (strpos($email,'@') !== false) {
    [$local,$domain] = explode('@',$email,2);
    $email = $local . '@' . mb_strtolower($domain);
}

$conn = db();

/* ========== 1) Throttling / lock check ========== */
$failCount   = 0;
$lockedUntil = null;

$st = $conn->prepare("SELECT fail_count, locked_until FROM login_attempts WHERE email=?");
$st->bind_param('s', $email);
$st->execute();
$st->bind_result($failCount, $lockedUntil);
$st->fetch();
$st->close();

if ($lockedUntil && (new DateTimeImmutable($lockedUntil)) > new DateTimeImmutable()) {
    http_response_code(429);
    echo json_encode(['ok'=>false, 'error'=>'Too many attempts. Try again later.']); exit;
}

/* helper: record a failed attempt with lock after N tries */
function record_failure(mysqli $conn, string $email, int $currentFailCount) {
    $count   = $currentFailCount + 1;
    $lockNow = ($count >= 5); // lock for 15 minutes after 5th failure

    if ($lockNow) {
        $q = "INSERT INTO login_attempts (email, fail_count, locked_until, last_failed_at)
          VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())
          ON DUPLICATE KEY UPDATE
            fail_count=VALUES(fail_count),
            locked_until=VALUES(locked_until),
            last_failed_at=VALUES(last_failed_at)";
        $stmt = $conn->prepare($q);
        $stmt->bind_param('si', $email, $count);
    } else {
        $q = "INSERT INTO login_attempts (email, fail_count, last_failed_at)
          VALUES (?, 1, NOW())
          ON DUPLICATE KEY UPDATE
            fail_count = fail_count + 1,
            last_failed_at = VALUES(last_failed_at)";
        $stmt = $conn->prepare($q);
        $stmt->bind_param('s', $email);
    }

    $stmt->execute();
    $stmt->close();

    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'Invalid credentials']); exit;
}

/* ========== 2) Load user ========== */
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, provider, avatar
                        FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    record_failure($conn, $email, (int)$failCount);
}

/* If this is a Google-only account (no local password), block local sign-in */
if ($user['provider'] === 'google' || is_null($user['password'])) {
    http_response_code(409);
    echo json_encode([
        'ok'      => false,
        'error'   => 'google_account',
        'message' => 'This email is registered with Google. Please continue with Google to sign in.'
    ]);
    exit;
}

/* ========== 3) Verify password ========== */
if (!password_verify($password, $user['password'])) {
    record_failure($conn, $email, (int)$failCount);
}

/* Optional: upgrade stored hash if PHP’s defaults changed */
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $up = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $up->bind_param('si', $newHash, $user['id']);
    $up->execute();
    $up->close();
}

/* ========== 4) Success: clear throttle & start session ========== */
$del = $conn->prepare("DELETE FROM login_attempts WHERE email=?");
$del->bind_param('s',$email);
$del->execute();
$del->close();

/* Renew session ID to prevent fixation */
session_regenerate_id(true);

$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['email']    = $user['email'];
$_SESSION['provider'] = $user['provider'] ?: 'local';
$_SESSION['avatar']   = $user['avatar'] ?? null;

/* Frontend will redirect to this URL on ok:true */
echo json_encode([
    'ok'       => true,
    'redirect' => '/matchymatchy/HTML/index.html'
]);

