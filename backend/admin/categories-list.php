<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../PHP/db.php';

try {
    $pdo = function_exists('pdo') ? pdo() : ((isset($pdo) && $pdo instanceof PDO) ? $pdo : null);
    if (!$pdo) throw new RuntimeException('PDO not initialized');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Top-level (parent_id is NULL or 0)
    $stmt = $pdo->query("
        SELECT id, name, slug, parent_id
        FROM categories
        WHERE status='active' AND (parent_id IS NULL OR parent_id = 0)
        ORDER BY name ASC
    ");
    echo json_encode(['ok'=>true,'data'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}

