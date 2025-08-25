<?php
// /matchymatchy/PHP/auth_db.php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * رجّع سجل المستخدم من DB بناءً على user_id الموجود في السيشن.
 * بيرجّع null إذا مش مسجل أو مش موجود بالسجّل.
 */
function auth_current_user(PDO $pdo): ?array {
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) return null;

    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, avatar, role, status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($row && isset($row['status']) && strtolower((string)$row['status']) !== 'active') {
        return null;
    }
    return $row;
}

/** helpers للردود JSON */
function auth_json_unauthorized(): void {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'unauthorized']);
    exit;
}
function auth_json_forbidden(): void {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'forbidden']);
    exit;
}

/** حماية صفحات HTML (توجيه) */
function require_login_page_db(PDO $pdo, string $loginUrl='/matchymatchy/sign-in.php'): array {
    $u = auth_current_user($pdo);
    if (!$u) {
        header("Location: {$loginUrl}?notice=" . urlencode('Please sign in.'));
        exit;
    }
    return $u;
}
function require_admin_page_db(PDO $pdo, string $loginUrl='/matchymatchy/sign-in.php'): array {
    $u = require_login_page_db($pdo, $loginUrl);
    $role = strtolower((string)($u['role'] ?? ''));
    $isAdmin = in_array($role, ['admin','administrator'], true);
    if (!$isAdmin) {
        header("Location: /matchymatchy/HTML/index.html");
        exit;
    }
    return $u;
}

/** حماية APIs (JSON) */
function require_login_api_db(PDO $pdo): array {
    $u = auth_current_user($pdo);
    if (!$u) auth_json_unauthorized();
    return $u;
}
function require_admin_api_db(PDO $pdo): array {
    $u = require_login_api_db($pdo);
    $role = strtolower((string)($u['role'] ?? ''));
    $isAdmin = in_array($role, ['admin','administrator'], true);
    if (!$isAdmin) auth_json_forbidden();
    return $u;
}
