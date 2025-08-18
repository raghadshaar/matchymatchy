<?php
// C:\xampp\htdocs\matchymatchy\PHP\logout.php
require __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: /matchymatchy/sign-in.php?notice=' . urlencode('You have been signed out.'));
exit;
