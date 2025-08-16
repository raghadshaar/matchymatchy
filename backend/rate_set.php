<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
ob_start();

try {
    $pdo = function_exists('pdo') ? pdo() : ((isset($pdo) && $pdo instanceof PDO) ? $pdo : null);
    if (!$pdo) throw new RuntimeException('PDO not initialized');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $productId = (int)($_POST['product_id'] ?? 0);
    $rating    = (int)($_POST['rating'] ?? 0); // 0 = امسح تقييمي
    if ($productId <= 0 || $rating < 0 || $rating > 5) {
        throw new RuntimeException('bad params');
    }

    $userId = currentUserId();
    $device = getDeviceHash();

    if ($rating === 0) {
        // حذف تقييمي
        if ($userId) {
            $pdo->prepare("DELETE FROM product_reviews WHERE product_id=? AND user_id=?")
                ->execute([$productId, $userId]);
        } else {
            $pdo->prepare("DELETE FROM product_reviews WHERE product_id=? AND device_hash=?")
                ->execute([$productId, $device]);
        }
        $myRating = 0;
    } else {
        // upsert
        if ($userId) {
            $pdo->prepare("
        INSERT INTO product_reviews (product_id, user_id, rating)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating)
      ")->execute([$productId, $userId, $rating]);
        } else {
            $pdo->prepare("
        INSERT INTO product_reviews (product_id, device_hash, rating)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating)
      ")->execute([$productId, $device, $rating]);
        }
        $myRating = $rating;
    }

    // حدّث المتوسط/العدد
    $stmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM product_reviews WHERE product_id=?");
    $stmt->execute([$productId]);
    $row = $stmt->fetch() ?: ['avg_rating'=>0, 'cnt'=>0];

    ob_clean();
    echo json_encode([
        'ok'        => true,
        'my_rating' => $myRating,                 // 0 يعني انمسح
        'avg'       => (float)$row['avg_rating'],
        'count'     => (int)$row['cnt'],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(200);
    $extra = ob_get_clean();
    echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'extra'=>$extra], JSON_UNESCAPED_UNICODE);
}
