<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));

// Neutral response for invalid email to avoid user enumeration
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => true]);
    exit;
}

$conn = db();

// Canonicalize domain case (optional but recommended)
if (strpos($email, '@') !== false) {
    [$local, $domain] = explode('@', $email, 2);
    $email = $local . '@' . mb_strtolower($domain);
}

/* ----------------------------------------------------------
   Simple throttle: if ≥3 sends in the last 15 minutes, no-op
   (Return ok:true so attacker can’t enumerate)
-----------------------------------------------------------*/
$stmt = $conn->prepare("SELECT send_count, last_sent_at FROM email_verifications WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $lastSentTs = strtotime($existing['last_sent_at'] ?? '1970-01-01 00:00:00');
    $within15m  = (time() - $lastSentTs) < (15 * 60);
    if ((int)$existing['send_count'] >= 3 && $within15m) {
        echo json_encode(['ok' => true]);
        exit;
    }
}

/* ----------------------------------------------------------
   Generate code + hash
   - Keep leading zeros with str_pad
   - Store only the hash (password_hash)
   - Issue a server token for later phases (if you use it)
-----------------------------------------------------------*/
$code       = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT); // "000123"… "999999"
$codeHash   = password_hash($code, PASSWORD_DEFAULT);
$serverToken= bin2hex(random_bytes(32)); // returned later by email-verify.php

/* ----------------------------------------------------------
   Upsert row using DB time for expiry and rate-limit counters
   (Use UPDATE if exists; otherwise INSERT)
-----------------------------------------------------------*/
if ($existing) {
    $stmt = $conn->prepare("
        UPDATE email_verifications
        SET code_hash    = ?,
            token        = ?,
            expires_at   = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
            verified_at  = NULL,
            attempt_count= 0,
            send_count   = IF(last_sent_at < (NOW() - INTERVAL 15 MINUTE), 1, send_count + 1),
            last_sent_at = NOW()
        WHERE email = ?
    ");
    $stmt->bind_param('sss', $codeHash, $serverToken, $email);
} else {
    $stmt = $conn->prepare("
        INSERT INTO email_verifications
            (email, code_hash, token, expires_at, verified_at, send_count, attempt_count, last_sent_at)
        VALUES
            (?,     ?,         ?,     DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL,        1,          0,            NOW())
    ");
    $stmt->bind_param('sss', $email, $codeHash, $serverToken);
}
$stmt->execute();
$stmt->close();

/* ----------------------------------------------------------
   Email the ACTUAL code (no hardcoded "123456")
-----------------------------------------------------------*/
$subject = 'Your Matchy Matchy verification code';

$safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
$html = "
  <div style='font-family:system-ui,-apple-system,Segoe UI,Roboto;line-height:1.5'>
    <p>Your verification code is:</p>
    <p style='font-size:22px;letter-spacing:3px'><strong>{$safeCode}</strong></p>
    <p>This code expires in 10 minutes.</p>
  </div>";
$text = "Your verification code is: {$code}\nThis code expires in 10 minutes.";

try {
    // sendEmail(to, subject, htmlBody, textBody) should be defined in config.php
    sendEmail($email, $subject, $html, $text);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    // Keep response neutral to avoid email existence probing
    // error_log('Verification email failed: ' . $e->getMessage());
    echo json_encode(['ok' => true]);
}
