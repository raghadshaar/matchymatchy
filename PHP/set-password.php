<?php
require __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$conn = db();

/* If someone opens this file with GET, bounce them to the HTML page */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';
    $email = $_GET['email'] ?? '';
    $qs = [];
    if ($token) $qs['token'] = $token;
    if ($email) $qs['email'] = $email;
    $query = $qs ? ('?' . http_build_query($qs)) : '';
    header('Location: /matchymatchy/HTML/set-password.html' . $query);
    exit;
}

/* ----- POST: save new password ----- */
$token = $_POST['token'] ?? '';
$p1    = $_POST['p1'] ?? '';
$p2    = $_POST['p2'] ?? '';

/* Basic checks */
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    http_response_code(400); echo 'Invalid or malformed link.'; exit;
}
if ($p1 !== $p2) {
    http_response_code(400); echo 'Passwords do not match.'; exit;
}
/* Keep same rules as your frontend */
$strong = strlen($p1) >= 8 && preg_match('/[A-Z]/',$p1) && preg_match('/[a-z]/',$p1) && preg_match('/\d/',$p1);
if (!$strong) {
    http_response_code(400); echo 'Password is too weak.'; exit;
}

$now = new DateTimeImmutable();

/* ===========================================================
   1) NEW FLOW (Google brand-new users): pending_google_signups
      - We mailed an opaque hex token (64 chars). In DB we keep SHA-256(token) as token_hash.
      - If this hits, we INSERT the user now (with hashed password) and delete the pending row.
   =========================================================== */
$tokenHash = hash('sha256', $token);

$stmt = $conn->prepare("
  SELECT id, google_id, email, first_name, last_name, avatar, email_verified, expires_at
  FROM pending_google_signups
  WHERE token_hash = ? AND expires_at > NOW()
  LIMIT 1
");
$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$pending = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($pending) {
    try {
        $conn->begin_transaction();

        // Double-check no user snuck in meanwhile
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR google_id = ? LIMIT 1");
        $stmt->bind_param('ss', $pending['email'], $pending['google_id']);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            // Clean up the pending row and ask user to sign in
            $stmt = $conn->prepare("DELETE FROM pending_google_signups WHERE id = ?");
            $stmt->bind_param('i', $pending['id']);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            http_response_code(409);
            echo 'Account already exists. Please sign in.'; exit;
        }

        // Insert user with hashed password (provider = google)
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        $verifiedAt = ((int)$pending['email_verified'] === 1) ? $now->format('Y-m-d H:i:s') : null;

        $stmt = $conn->prepare("
          INSERT INTO users
              (first_name, last_name, email, password, avatar, provider, google_id, email_verified_at, created_at)
          VALUES (?, ?, ?, ?, ?, 'google', ?, ?, NOW())
        ");
        $stmt->bind_param(
            'sssssss',
            $pending['first_name'],
            $pending['last_name'],
            $pending['email'],
            $hash,
            $pending['avatar'],
            $pending['google_id'],
            $verifiedAt
        );
        $stmt->execute();
        $userId = $stmt->insert_id;
        $stmt->close();

        // Consume pending record
        $stmt = $conn->prepare("DELETE FROM pending_google_signups WHERE id = ?");
        $stmt->bind_param('i', $pending['id']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Login and go home
        $_SESSION['user_id']  = $userId;
        $_SESSION['email']    = $pending['email'];
        $_SESSION['username'] = trim(($pending['first_name'] ?? '') . ' ' . ($pending['last_name'] ?? '')) ?: $pending['email'];
        $_SESSION['avatar']   = $pending['avatar'];
        $_SESSION['provider'] = 'google';

        header('Location: /matchymatchy/HTML/index.html');
        exit;

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        if ($e->getCode() === 1062) {
            http_response_code(409); echo 'Account already exists. Please sign in.'; exit;
        }
        http_response_code(500); echo 'Server error.'; exit;
    }
}

/* ===========================================================
   2) LEGACY/EXISTING FLOW (magic_links for existing users)
      - Your original logic, slightly hardened.
   =========================================================== */
$stmt = $conn->prepare("
  SELECT ml.id, ml.user_id, ml.expires_at, ml.used_at,
         u.email, u.first_name, u.last_name, u.avatar, u.provider
  FROM magic_links ml
  JOIN users u ON u.id = ml.user_id
  WHERE ml.token = ? AND ml.purpose = 'set_password'
  LIMIT 1
");
$stmt->bind_param('s', $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { http_response_code(400); echo 'Invalid or expired link.'; exit; }
if (!empty($row['used_at'])) { http_response_code(400); echo 'Link already used.'; exit; }
if (new DateTimeImmutable($row['expires_at']) < $now) {
    http_response_code(400); echo 'Link expired.'; exit;
}

// Hash & save the password (updates NULL -> real hash)
$hash = password_hash($p1, PASSWORD_DEFAULT);
$uid  = (int)$row['user_id'];

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param('si', $hash, $uid);
$stmt->execute();
$stmt->close();

// Mark this magic link used
$stmt = $conn->prepare("UPDATE magic_links SET used_at = NOW() WHERE id = ?");
$stmt->bind_param('i', $row['id']);
$stmt->execute();
$stmt->close();

/* log the user in and go to home */
$_SESSION['user_id']  = $uid;
$_SESSION['email']    = $row['email'];
$_SESSION['username'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: $row['email'];
$_SESSION['avatar']   = $row['avatar'];
$_SESSION['provider'] = $row['provider'] ?: 'google';

header('Location: /matchymatchy/HTML/index.html');
exit;
