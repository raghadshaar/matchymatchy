<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized']);
    exit;
}

try {
    $sizesStmt = $pdo->query("SELECT DISTINCT size_label FROM product_sizes ORDER BY size_label");
    $sizes = $sizesStmt->fetchAll(PDO::FETCH_COLUMN);

    $typesStmt = $pdo->query("SELECT name FROM product_types ORDER BY name");
    $types = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

    $fabricsStmt = $pdo->query("SELECT name FROM fabric_options ORDER BY name");
    $fabrics = $fabricsStmt->fetchAll(PDO::FETCH_COLUMN);

    $colorsStmt = $pdo->query("SELECT name FROM color_options ORDER BY name");
    $colors = $colorsStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'ok' => true,
        'data' => [
            'sizes' => $sizes,
            'types' => $types,
            'fabrics' => $fabrics,
            'colors' => $colors
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>