<?php
/**
 * Admin Reviews API (filters by parent category ID, rating, hidden, flagged, sort, search).
 */
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../PHP/db.php';
require_once __DIR__ . '/../util.php';

try {
    $pdo = function_exists('pdo') ? pdo() : ((isset($pdo) && $pdo instanceof PDO) ? $pdo : null);
    if (!$pdo) throw new RuntimeException('PDO not initialized');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Inputs
    $q        = trim((string)($_GET['q'] ?? ''));
    $catId    = (int)($_GET['cat_id'] ?? 0);              // parent category id
    $catSlug  = strtolower(trim((string)($_GET['cat'] ?? ''))); // optional fallback by slug
    $rating   = (string)($_GET['rating'] ?? '');
    $hidden   = (string)($_GET['hidden'] ?? '');
    $flagged  = (string)($_GET['flagged'] ?? '');
    $sort     = (string)($_GET['sort'] ?? 'new');         // new|old|high|low
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $size     = max(1, min(100, (int)($_GET['size'] ?? 10)));
    $offset   = ($page - 1) * $size;

    // WHERE
    $where = ['1=1'];
    $bind  = [];

    if ($q !== '') {
        $where[]      = "(p.name LIKE :q OR p.sku LIKE :q OR r.comment LIKE :q
                          OR u.email LIKE :q OR CONCAT_WS(' ', u.first_name, u.last_name) LIKE :q)";
        $bind[':q']   = "%{$q}%";
    }
    if ($rating !== '' && ctype_digit($rating)) {
        $where[]         = "r.rating = :rating";
        $bind[':rating'] = (int)$rating;
    }
    if ($hidden === '0') $where[] = "r.hidden = 0";
    if ($hidden === '1') $where[] = "r.hidden = 1";
    if ($flagged === '0') $where[] = "r.flagged = 0";
    if ($flagged === '1') $where[] = "r.flagged = 1";

    // Category by parent ID OR by slug
    if ($catId > 0) {
        // IMPORTANT: use TWO placeholders (PDO can’t reuse the same name twice) :contentReference[oaicite:1]{index=1}
        $where[] = "EXISTS (
            SELECT 1
            FROM product_categories pc
            JOIN categories c ON c.id = pc.category_id
            WHERE pc.product_id = r.product_id
              AND (c.id = :catId1 OR c.parent_id = :catId2)
        )";
        $bind[':catId1'] = $catId;
        $bind[':catId2'] = $catId;
    } elseif ($catSlug !== '') {
        $where[] = "EXISTS (
            SELECT 1
            FROM product_categories pc
            JOIN categories c ON c.id = pc.category_id
            WHERE pc.product_id = r.product_id
              AND (LOWER(c.slug) = :slugExact OR LOWER(c.slug) LIKE :slugPrefix)
        )";
        $bind[':slugExact']  = $catSlug;
        $bind[':slugPrefix'] = $catSlug.'%';
    }

    // Sort
    $order = "r.created_at DESC";
    if ($sort === 'old')  $order = "r.created_at ASC";
    if ($sort === 'high') $order = "r.rating DESC, r.created_at DESC";
    if ($sort === 'low')  $order = "r.rating ASC,  r.created_at DESC";

    // Count
    $sqlCount = "
      SELECT COUNT(*) AS c
      FROM product_reviews r
      LEFT JOIN products p ON p.id = r.product_id
      LEFT JOIN users    u ON u.id = r.user_id
      WHERE ".implode(' AND ', $where);
    $st = $pdo->prepare($sqlCount);
    foreach ($bind as $k=>$v) $st->bindValue($k,$v);
    $st->execute();
    $total = (int)($st->fetch()['c'] ?? 0);

    // Page
    $sql = "
      SELECT
        r.id, r.product_id, r.user_id, r.rating, r.comment,
        r.hidden, r.hidden_by, r.hidden_at,
        r.flagged, r.flag_reason, r.flagged_by, r.flagged_at,
        r.created_at, r.updated_at,
        p.name AS product_name, p.sku AS product_sku, p.image_main_url AS product_image,
        u.first_name, u.last_name, u.email, u.avatar
      FROM product_reviews r
      LEFT JOIN products p ON p.id = r.product_id
      LEFT JOIN users    u ON u.id = r.user_id
      WHERE ".implode(' AND ', $where)."
      ORDER BY $order
      LIMIT :lim OFFSET :off";
    $stmt = $pdo->prepare($sql);
    foreach ($bind as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->bindValue(':lim', $size,   PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = [];
    while ($r = $stmt->fetch()) {
        $rows[] = [
            'id'         => (int)$r['id'],
            'rating'     => (int)$r['rating'],
            'comment'    => (string)($r['comment'] ?? ''),
            'created_at' => (string)$r['created_at'],
            'updated_at' => (string)($r['updated_at'] ?? ''),
            'hidden'     => (int)$r['hidden'] === 1,
            'hidden_by'  => $r['hidden_by'] ? (int)$r['hidden_by'] : null,
            'hidden_at'  => (string)($r['hidden_at'] ?? ''),
            'flagged'     => (int)$r['flagged'] === 1,
            'flag_reason' => (string)($r['flag_reason'] ?? ''),
            'flagged_by'  => $r['flagged_by'] ? (int)$r['flagged_by'] : null,
            'flagged_at'  => (string)($r['flagged_at'] ?? ''),
            'product'    => [
                'id'    => (int)$r['product_id'],
                'name'  => (string)($r['product_name'] ?? ''),
                'sku'   => (string)($r['product_sku'] ?? ''),
                'image' => (string)($r['product_image'] ?? '../images/placeholder.jpg'),
            ],
            'user' => [
                'first_name' => (string)($r['first_name'] ?? ''),
                'last_name'  => (string)($r['last_name'] ?? ''),
                'email'      => (string)($r['email'] ?? ''),
                'avatar'     => (string)($r['avatar'] ?? ''),
            ],
        ];
    }

    echo json_encode(['ok'=>true,'total'=>$total,'data'=>$rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
