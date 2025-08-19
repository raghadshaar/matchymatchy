<?php
// C:\xampp\htdocs\matchymatchy\PHP\google-login-signin.php
require __DIR__ . '/config.php';

const BASE_URL = 'http://localhost/matchymatchy';

$client = makeGoogleClient();
// Use a dedicated callback for the *sign-in* flow:
$client->setRedirectUri(BASE_URL . '/PHP/google-callback-signin.php');
$client->setScopes(['openid', 'email', 'profile']);
$client->setAccessType('online');
$client->setIncludeGrantedScopes(true);
$client->setPrompt('select_account');

// CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2_state_signin'] = $state;
$client->setState($state);

// Kick off OAuth
header('Location: ' . $client->createAuthUrl());
exit;
