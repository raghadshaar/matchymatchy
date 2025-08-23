<?php
require_once __DIR__ . '/../PHP/db.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // عرض الألوان
        $stmt = $pdo->query("SELECT name, hex FROM color_options ORDER BY name");
        $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'colors' => $colors]);
        exit;
    }

    if ($method === 'POST') {
        // إضافة لون جديد
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim((string)($data['name'] ?? ''));
        $hex  = trim((string)($data['hex'] ?? ''));

        if ($name === '' || !preg_match('/^#([A-Fa-f0-9]{6})$/', $hex)) {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            exit;
        }

        // تأكد إذا كان موجود مسبقًا
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM color_options WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => true]); // موجود بالفعل
            exit;
        }

        $insert = $pdo->prepare("INSERT INTO color_options (name, hex) VALUES (?, ?)");
        $insert->execute([$name, $hex]);

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}