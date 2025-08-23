<?php
// /matchymatchy/backend/categories_api.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
define('APP_DEBUG', true);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../PHP/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'PDO not initialized (check PHP/db.php include)']);
    exit;
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* ------------ helpers ------------ */
function slugify(string $name): string {
    $s = strtolower(trim(preg_replace('~[^a-z0-9]+~i','-',$name),'-'));
    return $s ?: 'category';
}
function ensure_unique_slug(PDO $pdo, string $base, ?int $ignoreId=null): string {
    $slug = $base; $i = 2;
    while (true) {
        $sql = "SELECT id FROM categories WHERE slug=:slug" . ($ignoreId ? " AND id<>:id" : "") . " LIMIT 1";
        $st  = $pdo->prepare($sql);
        $st->bindValue(':slug', $slug);
        if ($ignoreId) $st->bindValue(':id', $ignoreId, PDO::PARAM_INT);
        $st->execute();
        if (!$st->fetchColumn()) return $slug;
        $slug = $base . '-' . $i++;
    }
}
function norm_status(string $s): string {
    return (strtolower($s)==='hidden') ? 'hidden' : 'active';
}
function title_status(string $s): string {
    return (strtolower($s)==='hidden') ? 'Hidden' : 'Active';
}

/* ------------ routing ------------ */
$method = $_SERVER['REQUEST_METHOD'];
/* ====== NAV tree for header (visible only) ====== */
/* ====== NAV tree for header (visible only) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['nav'])) {
    try {
        $rows = $pdo->query("SELECT id, name, slug, parent_id, status FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
        $parents = [];
        $childrenMap = [];

        foreach ($rows as $cat) {
            $cat['children'] = [];
            if ($cat['parent_id'] === null) {
                $parents[$cat['id']] = $cat;
            } else {
                $childrenMap[$cat['parent_id']][] = $cat;
            }
        }

        foreach ($childrenMap as $pid => $children) {
            if (isset($parents[$pid])) {
                $parents[$pid]['children'] = $children;
            }
        }

        // ترتيب حسب الأنواع المطلوبة
        usort($parents, function($a, $b) {
            $order = ['baby'=>1,'toddler'=>2,'kids'=>3,'toys'=>4,'deals'=>5,'collections'=>6];
            $aVal = $order[$a['slug']] ?? 999;
            $bVal = $order[$b['slug']] ?? 999;
            return $aVal <=> $bVal;
        });

        echo json_encode(['success' => true, 'categories' => array_values($parents)]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>(APP_DEBUG ? $e->getMessage() : 'database error')]);
        exit;
    }
}

/* ===== GET ===== */
if ($method === 'GET') {
    try {
        // parents list لملء قائمة الأب
        if (isset($_GET['parents'])) {
            $rows = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY name")->fetchAll();
            echo json_encode(['success'=>true,'parents'=>$rows]); exit;
        }

        // single
        if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
            $id = (int)$_GET['id'];

            $st = $pdo->prepare("SELECT c.*, p.name AS parent_name
                                 FROM categories c
                                 LEFT JOIN categories p ON p.id=c.parent_id
                                 WHERE c.id=? LIMIT 1");
            $st->execute([$id]);
            $c = $st->fetch();
            if (!$c) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

            // عدّ مباشر (فقط هذه الفئة)
            $stSelf = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id=?");
            $stSelf->execute([$id]);
            $self = (int)$stSelf->fetchColumn();

            // عدّ شامل الأبناء باستخدام CTE
            $sqlIncl = "
                WITH RECURSIVE tree AS (
                    SELECT id AS descendant_id FROM categories WHERE id = :cid
                    UNION ALL
                    SELECT c.id
                    FROM categories c
                    JOIN tree t ON c.parent_id = t.descendant_id
                )
                SELECT COUNT(DISTINCT pc.product_id)
                FROM product_categories pc
                JOIN tree t ON t.descendant_id = pc.category_id
            ";
            $st2 = $pdo->prepare($sqlIncl);
            $st2->execute([':cid'=>$id]);
            $incl = (int)$st2->fetchColumn();

            $c['product_count_direct']    = $self;
            $c['product_count_inclusive'] = $incl;
            $c['statusTitle']             = title_status((string)$c['status']);

            echo json_encode($c); exit;
        }

        /* ===== List (with filters & paging) ===== */
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 8)));
        $offset = ($page - 1) * $limit;

        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));     // "Active" | "Hidden" | ""
        $kind   = trim((string)($_GET['kind']   ?? ''));     // "" | "main" | "child"

        $w = []; $args = [];
        if ($search !== '') {
            $w[] = "(c.name LIKE :q OR c.description LIKE :q)";
            $args[':q'] = "%$search%";
        }
        if ($status !== '') {
            $statusDb = strtolower($status)==='hidden' ? 'hidden' : 'active';
            $w[] = "COALESCE(c.status,'active') = :st";
            $args[':st'] = $statusDb;
        }
        if ($kind === 'main')      $w[] = "c.parent_id IS NULL";
        elseif ($kind === 'child') $w[] = "c.parent_id IS NOT NULL";
        $whereSql = $w ? ('WHERE '.implode(' AND ', $w)) : '';

        // total
        $stCount = $pdo->prepare("SELECT COUNT(*) FROM categories c $whereSql");
        foreach($args as $k=>$v) $stCount->bindValue($k,$v);
        $stCount->execute();
        $total = (int)$stCount->fetchColumn();

        // counts: ابنِ كل (parent -> descendant) ثم DISTINCT على (parent, product)
        $sql = "
        WITH RECURSIVE descendants AS (
          SELECT id AS parent_id, id AS descendant_id
          FROM categories
          UNION ALL
          SELECT d.parent_id, c2.id
          FROM descendants d
          JOIN categories c2 ON c2.parent_id = d.descendant_id
        ),
        parent_product AS (
          SELECT DISTINCT d.parent_id AS category_id, pc.product_id
          FROM descendants d
          JOIN product_categories pc ON pc.category_id = d.descendant_id
        ),
        counts AS (
          SELECT category_id, COUNT(*) AS product_count
          FROM parent_product
          GROUP BY category_id
        )
        SELECT
           c.id, c.name, c.slug, c.parent_id, c.description, c.status, c.image_url,
           p.name AS parent_name,
           COALESCE(cnt.product_count,0) AS product_count
        FROM categories c
        LEFT JOIN categories p  ON p.id = c.parent_id
        LEFT JOIN counts    cnt ON cnt.category_id = c.id
        $whereSql
        ORDER BY c.name
        LIMIT :lim OFFSET :off";
        $st = $pdo->prepare($sql);
        foreach($args as $k=>$v) $st->bindValue($k,$v);
        $st->bindValue(':lim',$limit,PDO::PARAM_INT);
        $st->bindValue(':off',$offset,PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();

        foreach ($rows as &$r) {
            $t = strtolower(trim((string)($r['status'] ?? 'active')));
            $r['statusTitle'] = ($t==='hidden' ? 'Hidden' : 'Active');
        }

        $pages = max(1, (int)ceil($total / $limit));
        echo json_encode(['success'=>true,'categories'=>$rows,'total'=>$total,'pages'=>$pages]); exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=> (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : 'database error']); exit;
    }
}

/* ===== POST (create/update) ===== */
elseif ($method === 'POST') {
    $isJson = stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    $body = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

    $id        = isset($body['id']) ? (int)$body['id'] : null;
    $name      = trim((string)($body['name'] ?? ''));
    $parent_id = (int)($body['parent_id'] ?? 0);
    $desc      = (string)($body['description'] ?? '');
    $status    = norm_status((string)($body['status'] ?? 'active'));
    $image     = trim((string)($body['image_url'] ?? ''));

    if ($name==='') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'name required']); exit; }
    if ($id && $parent_id && $parent_id === $id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'parent cannot be itself']); exit; }

    $pdo->beginTransaction();
    try {
        if ($id) {
            $slug = ensure_unique_slug($pdo, slugify($name), $id);
            $st = $pdo->prepare("UPDATE categories
                                 SET name=:name, slug=:slug, parent_id=:pid, description=:d,
                                     image_url=:img, status=:st, updated_at=NOW()
                                 WHERE id=:id");
            $st->execute([
                ':name'=>$name, ':slug'=>$slug, ':pid'=>$parent_id ?: null,
                ':d'=>$desc, ':img'=>$image !== '' ? $image : null,
                ':st'=>$status, ':id'=>$id
            ]);
            $pdo->commit();
            echo json_encode(['success'=>true,'id'=>$id]); exit;

        } else {
            $slug = ensure_unique_slug($pdo, slugify($name), null);
            $st = $pdo->prepare("INSERT INTO categories(name, slug, parent_id, description, image_url, status, created_at, updated_at)
                                 VALUES(:name,:slug,:pid,:d,:img,:st,NOW(),NOW())");
            $st->execute([
                ':name'=>$name, ':slug'=>$slug, ':pid'=>$parent_id ?: null,
                ':d'=>$desc, ':img'=>$image !== '' ? $image : null,
                ':st'=>$status
            ]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->commit();
            echo json_encode(['success'=>true,'id'=>$newId]); exit;
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        $msg = APP_DEBUG ? $e->getMessage() : 'database error';
        if (APP_DEBUG) error_log('[categories_api POST] '.$e->getMessage());
        echo json_encode(['success'=>false,'error'=>$msg]); exit;
    }
}

/* ===== DELETE ===== */
elseif ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = isset($q['id']) ? (int)$q['id'] : 0;
    if ($id<=0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'bad id']); exit; }

    try {
        $stKids = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id=?");
        $stKids->execute([$id]);
        $kids = (int)$stKids->fetchColumn();
        if ($kids>0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Category has children']); exit; }

        $stLinks = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id=?");
        $stLinks->execute([$id]);
        $links = (int)$stLinks->fetchColumn();
        if ($links>0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Category linked to products']); exit; }

        $st = $pdo->prepare("DELETE FROM categories WHERE id=?");
        $st->execute([$id]);

        echo json_encode(['success'=>true]); exit;
    } catch (Throwable $e) {
        http_response_code(500);
        $msg = APP_DEBUG ? $e->getMessage() : 'database error';
        if (APP_DEBUG) error_log('[categories_api DELETE] '.$e->getMessage());
        echo json_encode(['success'=>false,'error'=>$msg]); exit;
    }
}

/* ===== Fallback ===== */
else {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit;
}
