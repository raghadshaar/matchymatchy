<?php
// /matchymatchy/backend/collections_api.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../PHP/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/**
 * يرجّع هيكل Parent + child1 لكل الجذور بالترتيب:
 * Baby, Toddler, Kids, Toys, Deals, Collections
 * الناتج:
 * [
 *   {"name":"Baby","slug":"baby","children":[{"name":"Baby Girl","slug":"baby-girl"}, ...]},
 *   ...
 * ]
 */

$rootSlugs = ['baby','toddler','kids','toys','deals','collections'];
$placeholders = implode(',', array_fill(0, count($rootSlugs), '?'));
$orderField  = implode(',', array_map(fn($s) => $pdo->quote($s), $rootSlugs));

try {
    // اجلب الجذور بالترتيب المطلوب
    $parentsSql = "
        SELECT id, name, slug
        FROM categories
        WHERE parent_id IS NULL AND slug IN ($placeholders)
        ORDER BY FIELD(slug, $orderField)
    ";
    $ps = $pdo->prepare($parentsSql);
    $ps->execute($rootSlugs);
    $parents = $ps->fetchAll(PDO::FETCH_ASSOC);

    // أبناء كل جذر
    $childStmt = $pdo->prepare("
        SELECT id, name, slug
        FROM categories
        WHERE parent_id = ?
        ORDER BY id
    ");

    $out = [];
    foreach ($parents as $p) {
        $childStmt->execute([$p['id']]);
        $children = $childStmt->fetchAll(PDO::FETCH_ASSOC);
        $out[] = [
            'name' => $p['name'],
            'slug' => $p['slug'],
            'children' => $children
        ];
    }

    echo json_encode(['ok'=>true, 'data'=>$out]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'failed to load collections']);
}
