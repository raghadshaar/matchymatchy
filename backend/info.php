<?php
// /backend/me.php
declare(strict_types=1);
require_once __DIR__ . '/../PHP/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

// مخرجات افتراضية
$out = ['ok'=>true, 'is_logged'=>false];

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode($out); // مش داخل
        exit;
    }

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'pdo_init_failed']);
        exit;
    }

    $uid = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, avatar, role, status
                             FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        echo json_encode($out);
        exit;
    }

    $full = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    if ($full === '') $full = $u['email'] ?? 'User';

    $out = [
        'ok'        => true,
        'is_logged' => true,
        'name'      => $full,
        'email'     => $u['email'] ?? '',
        'avatar'    => $u['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($full) . '&background=008080&color=fff',
        'role'      => $u['role'] ?? 'Customer',   // مثلاً: 'Administrator' / 'Customer'
        'status'    => $u['status'] ?? 'Active'
    ];
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'server_error']);
}
