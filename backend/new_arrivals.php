<?php
declare(strict_types=1);

// backend/new_arrivals.php
ini_set('display_errors','0');

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'PDO not initialized']); exit;
}

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// إن حبيتي تكمّلي بنفس الأسماء المكررة بدل الحل أدناه، فعّلي السطر التالي:
// $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

$userId = currentUserId() ?? 0;
$device = getDeviceHash();

/* ===== Config ===== */
$DAYS_DEFAULT = 3;  // نافذة "جديد" بالأيام
$USE_UTC = false;   // لو بتخزّني UTC، خلّيه true واستخدمي UTC_TIMESTAMP()

/* ===== Params ===== */
$page = max(1, (int)($_GET['page'] ?? 1));
$size = max(1, min(50, (int)($_GET['size'] ?? 24)));
$off  = ($page - 1) * $size;
$q    = trim((string)($_GET['q'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'created_desc'));
$days = max(1, (int)($_GET['days'] ?? $DAYS_DEFAULT));

$timeExpr = $USE_UTC ? 'UTC_TIMESTAMP()' : 'NOW()';
$newClause = "p.created_at >= {$timeExpr} - INTERVAL :days1 DAY";

/* ===== Base + args (استخدمنا أسماء فريدة) ===== */
$sqlBase = "FROM products p
LEFT JOIN (
  SELECT pc.product_id, GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS categories
  FROM product_categories pc
  JOIN categories c ON c.id = pc.category_id
  GROUP BY pc.product_id
) x ON x.product_id = p.id
LEFT JOIN v_product_metrics m ON m.product_id = p.id

LEFT JOIN (
  SELECT product_id, 1 AS my_like
  FROM product_likes
  WHERE (:uid1 > 0 AND user_id = :uid2) OR (:uid3 = 0 AND device_hash = :dvc1)
  GROUP BY product_id
) me_l ON me_l.product_id = p.id

LEFT JOIN (
  SELECT product_id, MAX(rating) AS my_rating
  FROM product_reviews
  WHERE (:uid4 > 0 AND user_id = :uid5) OR (:uid6 = 0 AND device_hash = :dvc2)
  GROUP BY product_id
) me_r ON me_r.product_id = p.id

WHERE {$newClause}";

$args = [
    ':uid1' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':dvc1' => $device,
    ':uid4' => $userId, ':uid5' => $userId, ':uid6' => $userId, ':dvc2' => $device,
    ':days1'=> $days,
];

/* بحث اختياري داخل الجديد */
if ($q !== '') {
    $sqlBase .= " AND (p.name LIKE :qname OR p.slug LIKE :qslug OR p.sku LIKE :qsku)";
    $args[':qname'] = "%{$q}%";
    $args[':qslug'] = "%{$q}%";
    $args[':qsku']  = "%{$q}%";
}

/* ترتيب */
$orderBy = 'p.created_at DESC';
switch ($sort) {
    case 'price_asc':  $orderBy = 'p.price ASC'; break;
    case 'price_desc': $orderBy = 'p.price DESC'; break;
    case 'name_asc':   $orderBy = 'p.name ASC'; break;
    case 'name_desc':  $orderBy = 'p.name DESC'; break;
}

/* العدّ */
$stmtCount = $pdo->prepare("SELECT COUNT(*) $sqlBase");
$stmtCount->execute($args);
$total = (int)$stmtCount->fetchColumn();

/* البيانات */
$sql = "SELECT
          p.id, p.name, p.slug, p.sku, p.description, p.price, p.currency,
          p.stock, p.status, p.image_main_url, x.categories,
          COALESCE(m.rating_avg,  NULL) AS rating_avg,
          COALESCE(m.rating_count,0)    AS rating_count,
          COALESCE(m.likes,       0)    AS likes,
          COALESCE(me_l.my_like,  0)    AS my_like,
          me_r.my_rating                AS my_rating,
          (p.created_at >= {$timeExpr} - INTERVAL :days2 DAY) AS is_new
        $sqlBase
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset";

$args[':days2'] = $days; // استخدمنا :days2 في الـ SELECT
$args[':limit'] = $size;
$args[':offset']= $off;

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$data = $stmt->fetchAll();

echo json_encode(['ok'=>true, 'data'=>$data, 'total'=>$total, 'page'=>$page, 'size'=>$size], JSON_UNESCAPED_UNICODE);
exit;
