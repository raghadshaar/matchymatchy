<?php
declare(strict_types=1);

require_once __DIR__ . '/_admin_boot.php';

try {
    $adminId = require_admin();
    $pdo = admin_pdo();

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);
    if ($id <= 0) json_err('bad id');

    $st = $pdo->prepare("DELETE FROM product_reviews WHERE id=?");
    $st->execute([$id]);

    json_ok();
} catch (Throwable $e) {
    json_err($e->getMessage());
}

