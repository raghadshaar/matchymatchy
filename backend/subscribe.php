<?php

require_once __DIR__ . '/../PHP/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Invalid method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid email']);
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['ok' => false, 'message' => 'Database not connected']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'message' => 'You are already subscribed']);
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO subscribers (email) VALUES (?)");
    $insert->execute([$email]);

    echo json_encode(['ok' => true, 'message' => 'Thanks for subscribing!']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
