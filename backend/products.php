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
$type     = trim($_GET['type'] ?? '');            // مثال: Dress
$fabrics  = isset($_GET['fabric']) ? (array)$_GET['fabric'] : []; // fabric[]=Cotton&fabric[]=Modal
$colors   = isset($_GET['color'])  ? (array)$_GET['color']  : []; // color[]=Pink&color[]=Ivory

function makeIn(string $prefix, array $vals, array &$args): string {
    $ph = [];
    foreach ($vals as $i => $v) {
        $k = ":{$prefix}{$i}";
        $ph[] = $k;
        $args[$k] = $v;
    }
    return $ph ? implode(',', $ph) : '';
}



if (isset($_GET['id']) || isset($_GET['product_id'])) {
    $id = (int)($_GET['id'] ?? $_GET['product_id']);

    // ===== المنتج الأساسي =====
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

    // ===== الصور =====
    $imgsStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $imgs = [];
    if ($imgsStmt->execute([$id])) { $imgs = $imgsStmt->fetchAll(PDO::FETCH_COLUMN); }
    if (!$imgs) { $imgs = [$prod['image_main_url']]; }

    // ===== المقاسات =====
    $sizesStmt = $pdo->prepare("SELECT size_label FROM product_sizes WHERE product_id = ? ORDER BY id ASC");
    $sizesStmt->execute([$id]);
    $sizes = $sizesStmt->fetchAll(PDO::FETCH_COLUMN);

    // ===== تصنيف/تصنيف فرعي (لـ breadcrumb) =====
    // نختار أَوّل تصنيف مرتبط بالمنتج مفضّلين التصنيف “الابن” (له parent_id)
    $catSql = "
      SELECT 
        c.id, c.name, c.slug, c.parent_id,
        p2.name AS parent_name, p2.slug AS parent_slug
      FROM product_categories pc
      JOIN categories c   ON c.id = pc.category_id
      LEFT JOIN categories p2 ON p2.id = c.parent_id
      WHERE pc.product_id = ?
      ORDER BY (c.parent_id IS NOT NULL) DESC, c.id ASC
      LIMIT 1
    ";
    $catStmt = $pdo->prepare($catSql);
    $catStmt->execute([$id]);
    $c = $catStmt->fetch(PDO::FETCH_ASSOC);

    // قيَم افتراضية لو ما في تصنيف
    $categoryName = null; $categorySlug = null;
    $subcategoryName = null; $subcategorySlug = null;
    if ($c) {
        if (!empty($c['parent_id'])) {
            // c = ابن => الأب هو الـ category، والابن هو subcategory
            $categoryName    = $c['parent_name'];
            $categorySlug    = $c['parent_slug'];
            $subcategoryName = $c['name'];
            $subcategorySlug = $c['slug'];
        } else {
            // c = أب بدون parent => هو الـ category فقط
            $categoryName    = $c['name'];
            $categorySlug    = $c['slug'];
            // لا subcategory
        }
    }

    // نبني مصفوفة breadcrumb بسيطة

    $breadcrumbs = [
        ['name' => 'Home', 'url' => 'index.html'],
    ];
    if ($categoryName && $categorySlug) {
        $breadcrumbs[] = ['name' => $categoryName, 'url' => "category.html?slug=" . urlencode($categorySlug)];
    }
    if ($subcategoryName && $subcategorySlug) {
        $breadcrumbs[] = ['name' => $subcategoryName, 'url' => "category.html?slug=" . urlencode($subcategorySlug)];
    }

    $breadcrumbs[] = ['name' => $prod['name']];

    // (اختياري) هل المستخدم عمل Like/Rating — حسب نظامك
    $my_like = 0;
    $my_rating = null;

    echo json_encode([
        'ok'   => true,
        'data' => [
            'id'              => (int)$prod['id'],
            'name'            => $prod['name'],
            'slug'            => $prod['slug'],
            'description'     => $prod['description'],
            'price'           => (float)$prod['price'],
            'image_main_url'  => $prod['image_main_url'],
            'images'          => $imgs,
            'sizes'           => $sizes,
            'rating_avg'      => $prod['rating_avg'] !== null ? (float)$prod['rating_avg'] : null,
            'rating_count'    => (int)$prod['rating_count'],
            'likes'           => (int)$prod['likes'],
            'my_like'         => (int)$my_like,
            'my_rating'       => $my_rating,

            // ==== حقول الـ breadcrumb ====
            'category'        => $categoryName,
            'category_slug'   => $categorySlug,
            'subcategory'     => $subcategoryName,
            'subcategory_slug'=> $subcategorySlug,
            'breadcrumbs'     => $breadcrumbs
        ]
    ], JSON_UNESCAPED_UNICODE);
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
    ':uid1' => $userId, ':uid2' => $userId, ':dvc1' => $device, ':uid3' => $userId,
    ':uid4' => $userId, ':uid5' => $userId, ':dvc2' => $device, ':uid6' => $userId,
];

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
    $catStmt = $pdo->prepare("SELECT id, parent_id FROM categories WHERE slug = ?");
    $catStmt->execute([$categorySlug]);
    $category = $catStmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        $categoryIds = [$category['id']];

        if ($category['parent_id'] === null) {
            $childStmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
            $childStmt->execute([$category['id']]);
            $children = $childStmt->fetchAll(PDO::FETCH_COLUMN);
            $categoryIds = array_merge($categoryIds, $children);
        }

        // إنشاء named placeholders للـ IN clause
        $inPlaceholders = [];
        foreach ($categoryIds as $index => $catId) {
            $placeholder = ':cat_id_' . $index;
            $inPlaceholders[] = $placeholder;
            $args[$placeholder] = $catId;
        }

        $sqlBase .= " AND EXISTS (
          SELECT 1
          FROM product_categories pc
          JOIN categories c2 ON c2.id = pc.category_id
          WHERE pc.product_id = p.id AND c2.id IN (" . implode(',', $inPlaceholders) . ")
        )";
    }
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


// ===== TYPE =====
if ($type !== '') {
    $sqlBase .= " AND EXISTS (
        SELECT 1 FROM product_types t
        WHERE t.id = p.type_id AND t.name = :type_name
    )";
    $args[':type_name'] = $type;
}

// ===== FABRIC =====
if (!empty($fabrics)) {
    $in = makeIn('fab_', $fabrics, $args);
    $sqlBase .= " AND EXISTS (
        SELECT 1
        FROM product_fabrics pf
        JOIN fabric_options f ON f.id = pf.fabric_id
        WHERE pf.product_id = p.id AND f.name IN ($in)
    )";
}

// ===== COLOR =====
if (!empty($colors)) {
    $in = makeIn('col_', $colors, $args);
    $sqlBase .= " AND EXISTS (
        SELECT 1
        FROM product_colors pc
        JOIN color_options co ON co.id = pc.color_id
        WHERE pc.product_id = p.id AND co.name IN ($in)
    )";
}
$sizes = isset($_GET['size']) ? (array)$_GET['size'] : [];

if ($type !== '') {
    $in = makeIn('size_', $sizes, $args);
    $sqlBase .= " AND EXISTS (
        SELECT 1 FROM product_sizes ps
        WHERE ps.product_id = p.id AND ps.size_label IN ($in)
    )";
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
          me_r.my_rating                AS my_rating,

          -- جديد: اسم النوع (قطعة/فستان/طقم..)
          (SELECT t.name
             FROM product_types t
            WHERE t.id = p.type_id) AS type_name,

          -- جديد: لستة الأقمشة CSV
          (SELECT GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ')
             FROM product_fabrics pf
             JOIN fabric_options f ON f.id = pf.fabric_id
            WHERE pf.product_id = p.id) AS fabrics_csv,

          -- جديد: لستة الألوان CSV
          (SELECT GROUP_CONCAT(co.name ORDER BY co.name SEPARATOR ', ')
             FROM product_colors pc
             JOIN color_options co ON co.id = pc.color_id
            WHERE pc.product_id = p.id) AS colors_csv

      
        $sqlBase
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset";


$args[':limit'] = $size;
$args[':offset'] = $off;

$stmt = $pdo->prepare($sql);
$stmt->execute($args);

$list = $stmt->fetchAll(PDO::FETCH_ASSOC);





echo json_encode(['ok' => true, 'data' => $list, 'page' => $page, 'size' => $size, 'total' => $total], JSON_UNESCAPED_UNICODE);
exit;




