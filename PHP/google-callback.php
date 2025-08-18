<?php
// C:\xampp\htdocs\matchymatchy\PHP\google-callback.php

require __DIR__ . '/config.php';
// If your config doesn't include composer autoload, uncomment the next line:
// require __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Base URL for redirects (use the one your app actually serves) */
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/matchymatchy');
}

/* Helper: redirect with a notice message */
function redirect_with_notice(string $path, string $notice) {
    $sep = (strpos($path, '?') === false) ? '?' : '&';
    header('Location: ' . $path . $sep . 'notice=' . urlencode($notice));
    exit;
}

$conn   = db();
$client = makeGoogleClient();

/* ---- 1) CSRF protection: check OAuth "state" ---- */
if (!isset($_GET['state']) || !hash_equals($_SESSION['oauth2_state'] ?? '', $_GET['state'])) {
    redirect_with_notice(BASE_URL . '/sign-in.php', 'State mismatch. Please try again.');
}
unset($_SESSION['oauth2_state']); // one-time use

/* ---- 2) Require authorization code ---- */
if (empty($_GET['code'])) {
    redirect_with_notice(BASE_URL . '/sign-in.php', 'Google sign-in was cancelled.');
}

try {
    /* ---- 3) Exchange code for tokens ---- */
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception($token['error_description'] ?? 'Token exchange failed');
    }
    $client->setAccessToken($token);

    /* ---- 4) Verify ID token server-side (recommended) ---- */
    $payload = null;
    if (!empty($token['id_token'])) {
        $payload = $client->verifyIdToken($token['id_token']);
    }

    $googleId = null;
    $email    = null;
    $name     = null;
    $avatar   = null;
    $verified = false;

    if ($payload) {
        $googleId = $payload['sub'] ?? null;
        $email    = $payload['email'] ?? null;
        $name     = trim(($payload['given_name'] ?? '') . ' ' . ($payload['family_name'] ?? ''));
        $avatar   = $payload['picture'] ?? null;
        $verified = !empty($payload['email_verified']);
    } else {
        // Fallback to UserInfo if ID token verify wasn't available
        $oauth2     = new Google_Service_Oauth2($client);
        $googleUser = $oauth2->userinfo->get();
        $googleId   = $googleUser->id;
        $email      = $googleUser->email;
        $name       = $googleUser->name;
        $avatar     = $googleUser->picture;
        $verified   = !empty($googleUser->verifiedEmail);
    }

    if (!$googleId || !$email) {
        throw new Exception('Missing Google ID or email');
    }

    // Canonicalize email domain (lowercase domain part)
    if (strpos($email, '@') !== false) {
        [$local, $domain] = explode('@', $email, 2);
        $email = $local . '@' . mb_strtolower($domain);
    }

    /* ---- 5) If account exists, do NOT send a link; ask them to Sign in ---- */
    $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->bind_param('ss', $googleId, $email);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        redirect_with_notice(BASE_URL . '/sign-in.php', 'Account already exists. Please sign in.');
    }

    /* ---- 6) Brand-new Google user → create pending record & email set-password link ---- */

    // Split name best-effort to fit NOT NULL first_name/last_name
    $first = trim($name);
    $last  = '';
    if ($first === '') { // no name from Google? use local part of email as first name
        $first = strstr($email, '@', true) ?: $email;
    } else if (strpos($first, ' ') !== false) {
        [$first, $last] = array_pad(explode(' ', $first, 2), 2, '');
    }

    // Opaque one-time token for the link (emailed). We store only its hash in DB.
    $opaqueToken = bin2hex(random_bytes(32));      // 64 hex chars
    $tokenHash   = hash('sha256', $opaqueToken);
    $ev          = $verified ? 1 : 0;

    // Use DB time for expiry to avoid timezone drift: expires in 30 minutes
    $stmt = $conn->prepare("
        INSERT INTO pending_google_signups
            (google_id, email, first_name, last_name, avatar, email_verified, token_hash, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
        ON DUPLICATE KEY UPDATE
            first_name    = VALUES(first_name),
            last_name     = VALUES(last_name),
            avatar        = VALUES(avatar),
            email_verified= VALUES(email_verified),
            token_hash    = VALUES(token_hash),
            expires_at    = VALUES(expires_at),
            created_at    = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param('sssssis', $googleId, $email, $first, $last, $avatar, $ev, $tokenHash);
    $stmt->execute();
    $stmt->close();

    // Build the set-password link
    $link = BASE_URL . '/HTML/set-password.html?token=' . urlencode($opaqueToken) . '&email=' . urlencode($email);

    // Compose email
    $safeName = htmlspecialchars($name ?: $email, ENT_QUOTES, 'UTF-8');
    $html = "
      <div style='font-family:system-ui,-apple-system,Segoe UI,Roboto;line-height:1.5'>
        <h2 style='color:#008080;margin:0 0 12px'>Set your password</h2>
        <p>Hi {$safeName},</p>
        <p>You signed in with Google. Please set a password to finish creating your Matchy Matchy account:</p>
        <p><a href='{$link}' style='display:inline-block;padding:10px 16px;background:#008080;color:#fff;border-radius:8px;text-decoration:none'>Set password</a></p>
        <p style='font-size:12px;color:#555'>This link expires in 30 minutes and can be used once.</p>
      </div>";
    $text = "Set your password:\n{$link}\n\nThis link expires in 30 minutes.";

    try {
        // sendEmail(to, subject, html, text) should exist in config.php
        sendEmail($email, 'Set your Matchy Matchy password', $html, $text);
    } catch (Throwable $mailErr) {
        // Not fatal — user can still click the link if they retrieve it otherwise
        error_log('Set-password email failed: ' . $mailErr->getMessage());
    }

    // Redirect to a "check your email" page
    header('Location: ' . BASE_URL . '/HTML/check-email.html?email=' . urlencode($email));
    exit;

} catch (Throwable $e) {
    error_log('Google callback error: ' . $e->getMessage());
    redirect_with_notice(BASE_URL . '/sign-in.php', 'Google sign-in failed. Please try again.');
}