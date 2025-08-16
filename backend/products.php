<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// لو بتفضّلي تتجنّبي تكرار الأسماء بالـplaceholders، ما في داعي تغيّري emulate.
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = currentUserId() ?? 0;
$device = getDeviceHash();

// ===== params =====
$q            = trim($_GET['q'] ?? '');
$categoryId   = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$categorySlug = trim($_GET['category_slug'] ?? '');
$status       = trim($_GET['status'] ?? '');  // in_stock | low_stock | out_of_stock | draft | archived | auto
$page         = max(1, (int)($_GET['page'] ?? 1));
$size         = max(1, min(50, (int)($_GET['size'] ?? 24)));
$off          = ($page - 1) * $size;
$sort         = trim($_GET['sort'] ?? '');    // price_asc | price_desc | name_asc | name_desc




if (isset($_GET['id']) || isset($_GET['product_id'])) {
    $id = (int)($_GET['id'] ?? $_GET['product_id']);

    // المنتج الأساسي
    $stmt = $pdo->prepare("
      SELECT p.id, p.name, p.slug, p.description, p.price, p.image_main_url,
             COALESCE(AVG(r.rating), NULL) AS rating_avg,
             COUNT(r.id) AS rating_count,
             (SELECT COUNT(*) FROM product_likes pl WHERE pl.product_id = p.id) AS likes
      FROM products p
      LEFT JOIN product_reviews r ON r.product_id = p.id
      WHERE p.id = ?
      GROUP BY p.id
      LIMIT 1
    ");
    $stmt->execute([$id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prod) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

    // الصور (إن وجدت لديك جدول للصور، أو أعد فقط image_main_url)
    $imgsStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $imgs = [];
    if ($imgsStmt->execute([$id])) { $imgs = $imgsStmt->fetchAll(PDO::FETCH_COLUMN); }
    if (!$imgs) { $imgs = [$prod['image_main_url']]; }

    // المقاسات
    $sizesStmt = $pdo->prepare("SELECT size_label FROM product_sizes WHERE product_id = ? ORDER BY id ASC");
    $sizesStmt->execute([$id]);
    $sizes = $sizesStmt->fetchAll(PDO::FETCH_COLUMN);

    // (اختياري) هل المستخدم عمل Like سابقًا؟ بناءً على user_id أو device_hash
    $my_like = 0; $my_rating = null;
    // ... حددي user_id من السيشن إن وجد

    // (اختياري) related products:
    // SELECT منتجات من نفس التصنيف

    echo json_encode([
        'ok'   => true,
        'data' => [
            'id'            => (int)$prod['id'],
            'name'          => $prod['name'],
            'slug'          => $prod['slug'],
            'description'   => $prod['description'],
            'price'         => (float)$prod['price'],
            'image_main_url'=> $prod['image_main_url'],
            'images'        => $imgs,
            'sizes'         => $sizes,
            'rating_avg'    => $prod['rating_avg'] !== null ? (float)$prod['rating_avg'] : null,
            'rating_count'  => (int)$prod['rating_count'],
            'likes'         => (int)$prod['likes'],
            'my_like'       => (int)$my_like,
            'my_rating'     => $my_rating
        ]
    ]);
    exit;
}
// ===== base + joins =====
$sqlBase = "FROM products p
LEFT JOIN (
  SELECT pc.product_id,
         GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS categories
  FROM product_categories pc
  JOIN categories c ON c.id = pc.category_id
  GROUP BY pc.product_id
) x ON x.product_id = p.id
LEFT JOIN v_product_metrics m ON m.product_id = p.id

-- حالتي أنا (لايك)
LEFT JOIN (
  SELECT product_id, 1 AS my_like
  FROM product_likes
  WHERE (user_id = :uid1 AND :uid2 > 0)
     OR (device_hash = :dvc1 AND :uid3 = 0)
  GROUP BY product_id
) me_l ON me_l.product_id = p.id

-- حالتي أنا (تقييمي)
LEFT JOIN (
  SELECT product_id, MAX(rating) AS my_rating
  FROM product_reviews
  WHERE (user_id = :uid4 AND :uid5 > 0)
     OR (device_hash = :dvc2 AND :uid6 = 0)
  GROUP BY product_id
) me_r ON me_r.product_id = p.id

WHERE 1";

$args = [
    // لاحظ: استخدمت أسماء مختلفة لتفادي تكرار نفس placeholder
    ':uid1' => $userId, ':uid2' => $userId, ':dvc1' => $device, ':uid3' => $userId,
    ':uid4' => $userId, ':uid5' => $userId, ':dvc2' => $device, ':uid6' => $userId,
];

// ===== filters (كلها بمسماة) =====
if ($q !== '') {
    $sqlBase .= " AND (p.name LIKE :qname OR p.sku LIKE :qsku)";
    $args[':qname'] = "%{$q}%";
    $args[':qsku']  = "%{$q}%";
}

if ($categoryId > 0) {
    $sqlBase .= " AND EXISTS (
      SELECT 1 FROM product_categories pc
      WHERE pc.product_id = p.id AND pc.category_id = :catId
    )";
    $args[':catId'] = $categoryId;
}

if ($categorySlug !== '') {
    $sqlBase .= " AND EXISTS (
      SELECT 1
      FROM product_categories pc
      JOIN categories c2 ON c2.id = pc.category_id
      WHERE pc.product_id = p.id AND c2.slug = :catSlug
    )";
    $args[':catSlug'] = $categorySlug;
}

if ($status !== '') {
    $sqlBase .= " AND (
      CASE
        WHEN p.status <> 'auto' THEN p.status
        WHEN p.stock <= 0 THEN 'out_of_stock'
        WHEN p.stock < 20 THEN 'low_stock'
        ELSE 'in_stock'
      END
    ) = :status";
    $args[':status'] = $status;
}

// ===== sort =====
$orderBy = 'p.created_at DESC';
switch ($sort) {
    case 'price_asc':  $orderBy = 'p.price ASC'; break;
    case 'price_desc': $orderBy = 'p.price DESC'; break;
    case 'name_asc':   $orderBy = 'p.name ASC'; break;
    case 'name_desc':  $orderBy = 'p.name DESC'; break;
}

// ===== count =====
$stmtCount = $pdo->prepare("SELECT COUNT(*) $sqlBase");
$stmtCount->execute($args);
$total = (int)$stmtCount->fetchColumn();

// ===== page data =====
$sql = "SELECT
          p.id, p.name, p.slug, p.sku, p.description, p.price, p.currency,
          p.stock, p.status, p.image_main_url, x.categories,
          COALESCE(m.rating_avg,  NULL) AS rating_avg,
          COALESCE(m.rating_count,0)    AS rating_count,
          COALESCE(m.likes,       0)    AS likes,
          COALESCE(me_l.my_like,  0)    AS my_like,
          me_r.my_rating                AS my_rating
        $sqlBase
        ORDER BY $orderBy
        LIMIT $size OFFSET $off";

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'data' => $list, 'page' => $page, 'size' => $size, 'total' => $total], JSON_UNESCAPED_UNICODE);
exit;
