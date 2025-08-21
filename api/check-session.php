<?php
require __DIR__ . '/../PHP/config.php';
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

echo json_encode([
    'ok'   => true,
    'auth' => !empty($_SESSION['user_id']),
    'user' => !empty($_SESSION['user_id']) ? [
        'id'       => (int)$_SESSION['user_id'],
        'email'    => $_SESSION['email'] ?? null,
        'provider' => $_SESSION['provider'] ?? null,
        'avatar'   => $_SESSION['avatar'] ?? null,
    ] : null
]);
