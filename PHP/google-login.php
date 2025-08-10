<?php
// C:\xampp\htdocs\your-app\google-login.php
require __DIR__ . '/config.php';

$client = makeGoogleClient();
$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;

