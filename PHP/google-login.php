<?php



require __DIR__ . '/config.php';

$client = makeGoogleClient();

// CSRF state
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2_state'] = $state;
$client->setState($state);

header('Location: ' . $client->createAuthUrl());
exit;