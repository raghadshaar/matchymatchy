<?php
// /matchymatchy/backend/products_api.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../PHP/db.php';     // must set $pdo = new PDO(...)
require_once __DIR__ . '/util.php';          // optional helpers if you have them
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* ---------------------------
   Helpers
----------------------------*/
function auto_status_from_stock(?int $stock): string {
    $s = max(0, (int)$stock);
    if ($s === 0) return 'Out of Stock';
    if ($s <= 10) return 'Low Stock';
    return 'In Stock';
}

// returns [ids...] of a parent category (by id or slug) including ALL its children
function get_descendant_category_ids(PDO $pdo, ?int $categoryId, ?string $slug): array {
    if (!$categoryId && !$slug) return [];
    if (!$categoryId) {
        $categoryId = (int)$pdo->query("SELECT id FROM categories WHERE slug=".$pdo->quote($slug)." LIMIT 1")->fetchColumn();
        if (!$categoryId) return [];
    }
    // MySQL 8+ recursive CTE
    $sql = "
        WITH RECURSIVE cats AS (
            SELECT id, parent_id FROM categories WHERE id = :root
            UNION ALL
            SELECT c.id, c.parent_id
            FROM categories c
            JOIN cats ON c.parent_id = cats.id
        )
        SELECT id FROM cats
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':root'=>$categoryId]);
    return array_map('intval', array_column($st->fetchAll(), 'id'));
}

function fetch_sizes(PDO $pdo, int $productId): array {
    $st = $pdo->prepare("SELECT size_label FROM product_sizes WHERE product_id=? ORDER BY size_label");
    $st->execute([$productId]);
    return array_column($st->fetchAll(), 'size_label');
}

function fetch_collections_str(PDO $pdo, int $productId): string {
    $sql = "SELECT GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS names
            FROM product_categories pc
            JOIN categories c ON c.id=pc.category_id
            WHERE pc.product_id=?";
    $st = $pdo->prepare($sql);
    $st->execute([$productId]);
    return (string)($st->fetchColumn() ?: '');
}

function persist_auto_status_if_needed(PDO $pdo, int $productId, ?string $dbStatus, int $stock, string $calc) {
    // Only write back if DB says Auto (by stock) or NULL/empty
    if ($dbStatus === null || $dbStatus === '' || strtolower($dbStatus) === 'auto (by stock)' || strtolower($dbStatus) === 'auto') {
        $st = $pdo->prepare("UPDATE products SET status=? WHERE id=?");
        $st->execute([$calc, $productId]);
    }
}

/* ---------------------------
   Routing
----------------------------*/
$method = $_SERVER['REQUEST_METHOD'];

/* GET (list or single) */
if ($method === 'GET') {
    // Single by id
    if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
        $id = (int)$_GET['id'];

        $st = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

        $calcStatus = auto_status_from_stock((int)$p['stock']);
        persist_auto_status_if_needed($pdo, $id, $p['status'] ?? null, (int)$p['stock'], $calcStatus);

        $p['status'] = $p['status'] ?: $calcStatus;
        $p['sizes']  = fetch_sizes($pdo, $id);

        // collections (readable string for your card) + raw ids if you want to edit later
        $p['collections'] = fetch_collections_str($pdo, $id);

        echo json_encode($p); exit;
    }

    // List
    $page       = max(1, (int)($_GET['page']  ?? 1));
    $limit      = max(1, min(100, (int)($_GET['limit'] ?? 8)));
    $offset     = ($page - 1) * $limit;
    $search     = trim((string)($_GET['search'] ?? ''));
    $status     = trim((string)($_GET['status'] ?? ''));                // In Stock / Low Stock / Out of Stock / Draft / Archived
    $collection = trim((string)($_GET['collection'] ?? ''));            // UI dropdown text (category name) — we’ll map it to category

    // Optional category filtering (for your requirement #3) by id or slug
    $category_id   = isset($_GET['category_id']) && ctype_digit((string)$_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $category_slug = isset($_GET['category_slug']) ? trim((string)$_GET['category_slug']) : null;

    $where = [];
    $args  = [];

    if ($search !== '') {
        $where[] = "(p.name LIKE :q OR p.sku LIKE :q)";
        $args[':q'] = "%$search%";
    }

    // If collection filter chosen, turn the name into category_id(s)
    if ($collection !== '') {
        $st = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $st->execute([$collection]);
        $collId = (int)($st->fetchColumn() ?: 0);
        if ($collId) {
            $ids = get_descendant_category_ids($pdo, $collId, null);
            if ($ids) {
                $where[] = "p.id IN (SELECT product_id FROM product_categories WHERE category_id IN (".implode(',',array_map('intval',$ids))."))";
            } else {
                // no matches
                echo json_encode(['products'=>[], 'total'=>0, 'pages'=>1]); exit;
            }
        }
    }

    // Parent/child category filter by id/slug (used by PLP or admin if you pass it)
    if ($category_id || $category_slug) {
        $ids = get_descendant_category_ids($pdo, $category_id, $category_slug);
        if ($ids) {
            $where[] = "p.id IN (SELECT product_id FROM product_categories WHERE category_id IN (".implode(',',array_map('intval',$ids))."))";
        } else {
            echo json_encode(['products'=>[], 'total'=>0, 'pages'=>1]); exit;
        }
    }

    // We will compute derived status; but allow hard filters (Draft/Archived) to pass directly
    $statusFilter = '';
    if ($status !== '') {
        if (in_array($status, ['Draft','Archived'], true)) {
            $where[] = "COALESCE(p.status,'') = :statusExact";
            $args[':statusExact'] = $status;
            $statusFilter = 'hard';
        } else {
            // In Stock / Low Stock / Out of Stock → we’ll filter later in PHP using derived status
            $statusFilter = $status;
        }
    }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    // Count
    $total = (int)$pdo->query("SELECT COUNT(*) FROM products p $whereSql")->fetchColumn();

    // Data
    $sql = "SELECT p.* FROM products p $whereSql ORDER BY p.id DESC LIMIT :lim OFFSET :off";
    $st  = $pdo->prepare($sql);
    foreach ($args as $k=>$v) $st->bindValue($k, $v);
    $st->bindValue(':lim', $limit,  PDO::PARAM_INT);
    $st->bindValue(':off', $offset, PDO::PARAM_INT);
    $st->execute();

    $rows = $st->fetchAll();

    // Enrich + auto-status + optional soft status filter
    $out = [];
    foreach ($rows as $r) {
        $calc = auto_status_from_stock((int)$r['stock']);
        persist_auto_status_if_needed($pdo, (int)$r['id'], $r['status'] ?? null, (int)$r['stock'], $calc);

        $r['status']      = $r['status'] ?: $calc;
        $r['collections'] = fetch_collections_str($pdo, (int)$r['id']);
        $r['sizes']       = fetch_sizes($pdo, (int)$r['id']);

        if ($statusFilter && $statusFilter !== 'hard') {
            if ($r['status'] !== $statusFilter) continue;
        }
        $out[] = $r;
    }

    $pages = max(1, (int)ceil($total / $limit));
    echo json_encode(['products'=>$out, 'total'=>$total, 'pages'=>$pages]); exit;
}

/* POST (create or update)
   Accepts:
   - JSON body (your existing UI)
   - OR multipart/form-data (if you submit a form)
*/
if ($method === 'POST') {
    $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    $body = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

    $id     = isset($body['id']) ? (int)$body['id'] : null;
    $name   = trim((string)($body['name'] ?? ''));
    $sku    = trim((string)($body['sku'] ?? ''));
    $price  = (float)($body['price'] ?? 0);
    $stock  = max(0, (int)($body['stock'] ?? 0));
    $desc   = (string)($body['description'] ?? '');
    $status = trim((string)($body['status'] ?? 'Auto (by stock)'));
    $image  = trim((string)($body['image'] ?? ''));
    $sizes  = (array)($body['sizes'] ?? []);
    $collectionName = trim((string)($body['collection'] ?? '')); // single dropdown in your UI

    if ($name==='' || $sku==='') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'name and sku are required']); exit; }

    // Calculate status if auto
    if ($status==='' || strtolower($status)==='auto (by stock)' || strtolower($status)==='auto') {
        $status = auto_status_from_stock($stock);
    }

    if ($id) {
        // Update
        $sql = "UPDATE products
                   SET name=:name, sku=:sku, price=:price, stock=:stock,
                       description=:d, status=:st, image_main_url=:img
                 WHERE id=:id";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':name'=>$name, ':sku'=>$sku, ':price'=>$price, ':stock'=>$stock,
            ':d'=>$desc, ':st'=>$status, ':img'=>$image, ':id'=>$id
        ]);

        // sizes
        $pdo->prepare("DELETE FROM product_sizes WHERE product_id=?")->execute([$id]);
        if ($sizes) {
            $ins = $pdo->prepare("INSERT INTO product_sizes(product_id,size_label) VALUES(?,?)");
            foreach ($sizes as $s) {
                $s = trim((string)$s); if ($s==='') continue;
                $ins->execute([$id, $s]);
            }
        }

        // category link (single collection)
        if ($collectionName !== '') {
            $cid = (int)$pdo->query("SELECT id FROM categories WHERE name=".$pdo->quote($collectionName)." LIMIT 1")->fetchColumn();
            if ($cid) {
                // ensure at least this link exists
                $pdo->prepare("INSERT IGNORE INTO product_categories(product_id,category_id) VALUES(?,?)")->execute([$id,$cid]);
            }
        }

        echo json_encode(['success'=>true, 'id'=>$id]); exit;
    } else {
        // Create
        $sql = "INSERT INTO products(name, slug, sku, description, price, stock, status, image_main_url)
                VALUES(:name, LOWER(REPLACE(:name,' ','-')), :sku, :d, :price, :stock, :st, :img)";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':name'=>$name, ':sku'=>$sku, ':d'=>$desc, ':price'=>$price, ':stock'=>$stock,
            ':st'=>$status, ':img'=>$image
        ]);
        $newId = (int)$pdo->lastInsertId();

        // sizes
        if ($sizes) {
            $ins = $pdo->prepare("INSERT INTO product_sizes(product_id,size_label) VALUES(?,?)");
            foreach ($sizes as $s) {
                $s = trim((string)$s); if ($s==='') continue;
                $ins->execute([$newId, $s]);
            }
        }

        // category (collection)
        if ($collectionName !== '') {
            $cid = (int)$pdo->query("SELECT id FROM categories WHERE name=".$pdo->quote($collectionName)." LIMIT 1")->fetchColumn();
            if ($cid) {
                $pdo->prepare("INSERT IGNORE INTO product_categories(product_id,category_id) VALUES(?,?)")->execute([$newId,$cid]);
            }
        }

        echo json_encode(['success'=>true, 'id'=>$newId]); exit;
    }
}

/* DELETE /backend/products_api.php?id=123 */
if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = isset($q['id']) ? (int)$q['id'] : 0;
    if ($id<=0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'bad id']); exit; }

    $pdo->prepare("DELETE FROM product_sizes WHERE product_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM product_categories WHERE product_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);

    echo json_encode(['success'=>true]); exit;
}

http_response_code(405);
echo json_encode(['success'=>false,'error'=>'Method not allowed']);
