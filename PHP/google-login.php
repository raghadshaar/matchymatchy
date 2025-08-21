<?php
// C:\xampp\htdocs\matchymatchy\google-login.php
require __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* If already signed in, skip OAuth */
if (!empty($_SESSION['user_id'])) {
    header('Location: /matchymatchy/HTML/index.html');
    exit;
}

/* Optional: allow ?next=/path to redirect after callback */
if (!empty($_GET['next'])) {
    // Basic allow-list: only relative paths under /matchymatchy
    $next = (string)$_GET['next'];
    if (strpos($next, '/') === 0 && strpos($next, '..') === false) {
        $_SESSION['post_login_redirect'] = $next;
    }
}

/* Build Google client */
$client = makeGoogleClient();

/* Ensure scopes include OIDC */
$client->setScopes(['openid', 'email', 'profile']);

/* Ask user to pick an account each time (nice for shared machines) */
$client->setPrompt('select_account');  // you can use 'consent select_account' if you also want consent UI

/* Optional: pre-fill email in Google’s chooser via ?email= */
if (!empty($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $client->setLoginHint($_GET['email']);
}

/* CSRF protection: state */
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2_state'] = $state;
$client->setState($state);

/* IMPORTANT: Redirect URI must match what you set in makeGoogleClient()
   Typically it’s: http://localhost/matchymatchy/PHP/google-callback.php
   (makeGoogleClient() should already set it) */

/* Kick off OAuth */
$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
