<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
ob_start();



try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId <= 0) throw new RuntimeException('bad product_id');

    $userId = currentUserId();
    $device = getDeviceHash();
    $liked  = false;

    if ($userId) {
        try {
            $pdo->prepare("INSERT INTO product_likes (product_id,user_id) VALUES (?,?)")
                ->execute([$productId,$userId]);
            $liked = true;
        } catch (PDOException $e) {
            if ($e->getCode()==='23000') {
                $pdo->prepare("DELETE FROM product_likes WHERE product_id=? AND user_id=?")
                    ->execute([$productId,$userId]);
                $liked = false;
            } else { throw $e; }
        }
    } else {
        try {
            $pdo->prepare("INSERT INTO product_likes (product_id,device_hash) VALUES (?,?)")
                ->execute([$productId,$device]);
            $liked = true;
        } catch (PDOException $e) {
            if ($e->getCode()==='23000') {
                $pdo->prepare("DELETE FROM product_likes WHERE product_id=? AND device_hash=?")
                    ->execute([$productId,$device]);
                $liked = false;
            } else { throw $e; }
        }
    }

    $likes = (int)$pdo->query("SELECT COUNT(*) FROM product_likes WHERE product_id=".(int)$productId)->fetchColumn();

    ob_clean();
    echo json_encode(['ok'=>true,'liked'=>$liked,'likes'=>$likes], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(200);
    $extra = ob_get_clean();
    echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'extra'=>$extra], JSON_UNESCAPED_UNICODE);
}
