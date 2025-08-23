<?php
session_start();
require_once __DIR__ . '/../PHP/db.php';

header('Content-Type: application/json; charset=utf-8');

// تحديد هوية المستخدم أو الزائر
$userId     = $_SESSION['user_id'] ?? null;
$deviceHash = $_COOKIE['device_hash'] ?? null;

// تحقق من وجود الهوية
if (!$userId && !$deviceHash) {
    echo json_encode(['success' => false, 'error' => 'User not logged in or device not recognized']);
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Helper: فحص وجود جدول
    $tableExists = function(PDO $pdo, string $t): bool {
        try { $pdo->query("SELECT 1 FROM `$t` LIMIT 1"); return true; }
        catch (Throwable $e) { return false; }
    };

    // حددي جدول التقييمات المتوفر (product_ratings أو product_reviews) أو لا شيء
    $ratingTable = null;
    if ($tableExists($pdo, 'product_ratings'))  $ratingTable = 'product_ratings';
    elseif ($tableExists($pdo, 'product_reviews')) $ratingTable = 'product_reviews';

    // أعمدة التقييم المحسوبة بشكل آمن
    if ($ratingTable) {
        $ratingAvgCountSql = "
            (SELECT ROUND(AVG(r.rating),1) FROM `$ratingTable` r WHERE r.product_id = p.id) AS rating_avg,
            (SELECT COUNT(*)                 FROM `$ratingTable` r WHERE r.product_id = p.id) AS rating_count
        ";
        // my_rating للمستخدم المسجّل فقط (عشان ما نغلط لو جدولك ما فيه device_hash)
        if ($userId) {
            $myRatingSql = "
                (SELECT r.rating FROM `$ratingTable` r
                  WHERE r.product_id = p.id AND r.user_id = ?
                  ORDER BY r.id DESC LIMIT 1) AS my_rating
            ";
        } else {
            $myRatingSql = "NULL AS my_rating";
        }
    } else {
        $ratingAvgCountSql = "NULL AS rating_avg, 0 AS rating_count";
        $myRatingSql       = "NULL AS my_rating";
    }

    // WHERE حسب الهوية (نفس منطقك)
    $whereClause = $userId ? "pl.user_id = ?" : "pl.device_hash = ?";

    // بارامترات الاستعلام (نحط userId أول لو بدنا نستخدمه في subquery my_rating)
    $params = [];
    if ($userId && $ratingTable) { $params[] = $userId; }
    // بارامتر الـWHERE دائماً آخر واحد:
    $params[] = $userId ?? $deviceHash;

    // جلب المنتجات المفضلة + الحقول الإضافية المطلوبة للكارد
    $sql = "
        SELECT 
            p.id, p.name, p.slug, p.price, p.image_main_url, 
            COALESCE(pl.user_id, 0)                               AS liked_by_user,
            pl.created_at                                         AS liked_at,

            -- إجمالي اللايكات لهذا المنتج
            (SELECT COUNT(*) FROM product_likes x WHERE x.product_id = p.id) AS likes,

            -- بما أن الصفحة Wishlist فالعنصر أكيد معمول له لايك
            1 AS my_like,

            $ratingAvgCountSql,
            $myRatingSql

        FROM product_likes pl
        JOIN products p ON p.id = pl.product_id
        WHERE $whereClause
        ORDER BY pl.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $wishlist = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $wishlist]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error_details' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
