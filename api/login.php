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

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'invalid_input','message'=>'Please enter a valid email and password.']); exit;
}

// normalize domain case
if (strpos($email,'@') !== false) {
    [$local,$domain] = explode('@',$email,2);
    $email = $local . '@' . mb_strtolower($domain);
}

$conn = db();

/* --- read throttling status --- */
$failCount   = 0;
$lockedUntil = null;

$st = $conn->prepare("SELECT fail_count, locked_until FROM login_attempts WHERE email=?");
$st->bind_param('s', $email);
$st->execute();
$st->bind_result($failCount, $lockedUntil);
$st->fetch();
$st->close();

if ($lockedUntil && (new DateTimeImmutable($lockedUntil)) > new DateTimeImmutable()) {
    $remain = (new DateTimeImmutable($lockedUntil))->getTimestamp() - time();
    if ($remain < 0) $remain = 0;
    http_response_code(429);
    echo json_encode([
        'ok'=>false,
        'error'=>'locked',
        'message'=>'Too many attempts. Try again later.',
        'lock_remaining_sec'=>$remain
    ]);
    exit;
}

/* helper: record failed login */
function record_failure(mysqli $conn, string $email, int $currentFailCount) {
    $count   = $currentFailCount + 1;
    $lockNow = ($count >= 5); // lock after 5 fails

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
}

/* --- load user by email --- */
/* --- load user by email --- */
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, provider, avatar, role
                        FROM users WHERE email=? LIMIT 1");  // ← added role

$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // email not found -> tell user to sign up (per your requirement)
    http_response_code(404);
    echo json_encode([
        'ok'=>false,
        'error'=>'no_account',
        'message'=>'No account found for this email. Please sign up.'
    ]);
    exit;
}

/* allow login even if provider='google' as long as a local password exists */
if (is_null($user['password'])) {
    http_response_code(400);
    echo json_encode([
        'ok'=>false,
        'error'=>'no_local_password',
        'message'=>'This account does not have a password yet. Use Continue with Google or click Forgot Password to set one.'
    ]);
    exit;
}

/* verify password */
if (!password_verify($password, $user['password'])) {
    record_failure($conn, $email, (int)$failCount);
    http_response_code(400);
    echo json_encode([
        'ok'=>false,
        'error'=>'bad_credentials',
        'message'=>'Incorrect email or password.'
    ]);
    exit;
}

/* optional: upgrade hash if needed */
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $up = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $up->bind_param('si', $newHash, $user['id']);
    $up->execute(); $up->close();
}

/* success: clear throttling row */
$del = $conn->prepare("DELETE FROM login_attempts WHERE email=?");
$del->bind_param('s', $email);
$del->execute(); $del->close();

/* renew session */
session_regenerate_id(true);
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['email']    = $user['email'];
$_SESSION['provider'] = $user['provider'] ?: 'local';
$_SESSION['avatar']   = $user['avatar'] ?? null;
$_SESSION['role'] = strtolower((string)$user['role']);


$redirect = (strtolower((string)$user['role']) === 'administrator')
    ? '/matchymatchy/HTML/admin.html'
    : '/matchymatchy/HTML/index.html';

echo json_encode([
    'ok'         => true,
    'redirect'   => $redirect,
    'role'       => strtolower((string)$user['role']),
    'user_id'    => (int)$user['id'],
    'first_name' => $user['first_name'],
    'email'      => $user['email']
]);

