<?php
// C:\xampp\htdocs\matchymatchy\PHP\google-callback-signin.php
require __DIR__ . '/config.php';

const BASE_URL = 'http://localhost/matchymatchy';

function back_to_signin(string $msg) {
    $to = BASE_URL . '/sign-in.php?notice=' . urlencode($msg);
    header('Location: ' . $to);
    exit;
}

try {
    // Basic checks
    if (!isset($_GET['state']) || !hash_equals($_SESSION['oauth2_state_signin'] ?? '', $_GET['state'])) {
        unset($_SESSION['oauth2_state_signin']);
        back_to_signin('Security check failed. Please try again.');
    }
    unset($_SESSION['oauth2_state_signin']);

    if (isset($_GET['error'])) {
        back_to_signin('Google sign-in was cancelled.');
    }
    if (empty($_GET['code'])) {
        back_to_signin('Missing authorization code.');
    }

    // Get Google user
    $client  = makeGoogleClient();
    $client->setRedirectUri(BASE_URL . '/PHP/google-callback-signin.php');

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        error_log('Google token error: ' . ($token['error_description'] ?? $token['error']));
        back_to_signin('Google sign-in failed. Please try again.');
    }
    $client->setAccessToken($token);

    $svc   = new Google_Service_Oauth2($client);
    $guser = $svc->userinfo->get();

    $googleId = $guser->id ?? null;
    $email    = $guser->email ?? null;
    $avatar   = $guser->picture ?? null;
    $name     = $guser->name ?? '';
    if (!$email) {
        back_to_signin('Could not retrieve your Google email.');
    }

    // Look up user: by google_id OR email (existing account only)
    $conn = db();
    $stmt = $conn->prepare("SELECT id, email, first_name, last_name, provider FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->bind_param('ss', $googleId, $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // Do NOT create. This is a sign-in-only flow.
        back_to_signin('No account found for that Google email. Please sign up.');
    }

    // Optionally link google_id & avatar for future convenience
    if ($googleId) {
        $stmt = $conn->prepare("UPDATE users SET google_id = IFNULL(google_id, ?), avatar = ?, provider='google' WHERE id = ?");
        $stmt->bind_param('ssi', $googleId, $avatar, $user['id']);
        $stmt->execute();
        $stmt->close();
    }

    // Start session
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['email']    = $email;
    $_SESSION['avatar']   = $avatar;
    $_SESSION['provider'] = 'google';
    $_SESSION['username'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $email;

    // Go to home (or wherever you want after sign-in)
    header('Location: ' . BASE_URL . '/HTML/index.html');
    exit;

} catch (Throwable $e) {
    error_log('google-callback-signin error: ' . $e->getMessage());
    back_to_signin('Google sign-in failed. Please try again.');
}
