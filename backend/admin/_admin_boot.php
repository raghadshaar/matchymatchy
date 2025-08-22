<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../PHP/db.php';
require_once __DIR__ . '/../util.php';

function admin_pdo(): PDO {
    $pdo = function_exists('pdo') ? pdo() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) throw new RuntimeException('PDO not initialized');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function require_admin(): int {
    $uid = currentUserId();             // implement in util.php
    if (!$uid /* || !isAdmin($uid) */) { // enable isAdmin() if you have roles
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'Forbidden']);
        exit;
    }
    return $uid;
}

function json_ok(array $extra=[]): never { echo json_encode(['ok'=>true] + $extra, JSON_UNESCAPED_UNICODE); exit; }
function json_err(string $msg, int $code=200, array $extra=[]): never {
    http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg] + $extra, JSON_UNESCAPED_UNICODE); exit;
}
