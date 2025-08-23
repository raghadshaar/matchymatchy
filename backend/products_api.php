<?php
// /matchymatchy/backend/products_api.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
define('APP_DEBUG', true);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../PHP/db.php';     // يجب أن يعرّف $pdo = new PDO(...)
require_once __DIR__ . '/util.php';          // اختياري
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
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

// يرجّع كل أبناء الفئة (أحفاد أيضًا) لاستخدامها بتصفية الأب + أبنائه (MySQL 8+)
function get_descendant_category_ids(PDO $pdo, ?int $categoryId, ?string $slug): array {
    if (!$categoryId && !$slug) return [];
    if (!$categoryId) {
        $categoryId = (int)$pdo->query("SELECT id FROM categories WHERE slug=".$pdo->quote($slug)." LIMIT 1")->fetchColumn();
        if (!$categoryId) return [];
    }
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
function fetch_fabrics(PDO $pdo, int $productId): array {
    $sql = "SELECT f.name FROM product_fabrics pf
            JOIN fabric_options f ON f.id = pf.fabric_id
            WHERE pf.product_id = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$productId]);
    return array_column($st->fetchAll(), 'name');
}

function fetch_colors(PDO $pdo, int $productId): array {
    $sql = "SELECT c.name FROM product_colors pc
            JOIN color_options c ON c.id = pc.color_id
            WHERE pc.product_id = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$productId]);
    return array_column($st->fetchAll(), 'name');
}
function fetch_type(PDO $pdo, int $productId): ?string {
    $sql = "SELECT t.name FROM product_types t
            JOIN products p ON p.type_id = t.id
            WHERE p.id = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$productId]);
    return $st->fetchColumn() ?: null;
}


function fetch_images(PDO $pdo, int $productId): array {
    $st = $pdo->prepare("
        SELECT id, image_url, alt_text, sort_order
        FROM product_images
        WHERE product_id = ?
        ORDER BY sort_order, id
    ");
    $st->execute([$productId]);
    return $st->fetchAll();
}

// يعيد أي كاتيجوري مرتبط ويفضّل الطفل (اللي له parent)
function fetch_primary_category(PDO $pdo, int $productId): ?array {
    $sql = "
      SELECT c.id, c.name, c.parent_id, p.name AS parent_name
      FROM product_categories pc
      JOIN categories c ON c.id = pc.category_id
      LEFT JOIN categories p ON p.id = c.parent_id
      WHERE pc.product_id = ?
      ORDER BY (c.parent_id IS NULL) ASC, c.id DESC
      LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$productId]);
    $row = $st->fetch();
    return $row ?: null;
}

function persist_auto_status_if_needed(PDO $pdo, int $productId, ?string $dbStatus, int $stock, string $calc) {
    // نكتب الحالة المحسوبة فقط إذا الحالة محفوظة Auto (by stock) أو NULL/فارغة
    if ($dbStatus === null || $dbStatus === '' || strtolower($dbStatus) === 'auto (by stock)' || strtolower($dbStatus) === 'auto') {
        $st = $pdo->prepare("UPDATE products SET status=? WHERE id=?");
        $st->execute([$calc, $productId]);
    }
}
function get_or_create_color_id(PDO $pdo, string $colorName): ?int {
    $colorName = trim($colorName);
    if ($colorName === '') return null;

    $stmt = $pdo->prepare("SELECT id FROM color_options WHERE name = ?");
    $stmt->execute([$colorName]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    $stmt = $pdo->prepare("INSERT INTO color_options (name) VALUES (?)");
    $stmt->execute([$colorName]);
    return (int)$pdo->lastInsertId();
}


/* ---------------------------
   Routing
----------------------------*/
$method = $_SERVER['REQUEST_METHOD'];

/* ===========================
   GET (single or list)
   =========================== */
if ($method === 'GET') {
    try {
        // ----- Single by ID -----
        if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
            $id = (int)$_GET['id'];

            $st = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
            $st->execute([$id]);
            $p = $st->fetch();
            if (!$p) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Product not found']);
                exit;
            }

            $calcStatus = auto_status_from_stock((int)$p['stock']);
            persist_auto_status_if_needed($pdo, $id, $p['status'] ?? null, (int)$p['stock'], $calcStatus);

            // enrich
            $p['status']      = $p['status'] ?: $calcStatus;
            $p['sizes']       = fetch_sizes($pdo, $id);
            $p['images']      = fetch_images($pdo, $id);
            $p['collections'] = fetch_collections_str($pdo, $id);
            $p['type']        = fetch_type($pdo, $id);
            $p['fabrics']     = fetch_fabrics($pdo, $id);
            $p['colors']      = fetch_colors($pdo, $id);

            $cat = fetch_primary_category($pdo, $id);
            if ($cat) {
                $p['category_id']     = (int)$cat['id'];
                $p['category_parent'] = $cat['parent_name'] ?? '';
            }

            echo json_encode($p);
            exit;
        }

        // ----- List with filters -----
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $limit      = max(1, min(100, (int)($_GET['limit'] ?? 8)));
        $offset     = ($page - 1) * $limit;

        $search     = trim((string)($_GET['search'] ?? ''));
        $status     = trim((string)($_GET['status'] ?? ''));
        $collection = trim((string)($_GET['collection'] ?? ''));
        $category_id   = isset($_GET['category_id']) && ctype_digit((string)$_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $category_slug = isset($_GET['category_slug']) ? trim((string)$_GET['category_slug']) : null;

        $where = [];
        $args  = [];

        if ($search !== '') {
            $where[]     = "(p.name LIKE :q1 OR p.sku LIKE :q2)";
            $args[':q1'] = "%$search%";
            $args[':q2'] = "%$search%";
        }

        if ($collection !== '') {
            $st = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
            $st->execute([$collection]);
            $collId = (int)($st->fetchColumn() ?: 0);
            if ($collId) {
                $ids = get_descendant_category_ids($pdo, $collId, null);
                if ($ids) {
                    $where[] = "p.id IN (SELECT product_id FROM product_categories WHERE category_id IN (" . implode(',', array_map('intval', $ids)) . "))";
                } else {
                    echo json_encode(['products' => [], 'total' => 0, 'pages' => 1]); exit;
                }
            }
        }

        if ($category_id || $category_slug) {
            $ids = get_descendant_category_ids($pdo, $category_id, $category_slug);
            if ($ids) {
                $where[] = "p.id IN (SELECT product_id FROM product_categories WHERE category_id IN (" . implode(',', array_map('intval', $ids)) . "))";
            } else {
                echo json_encode(['products' => [], 'total' => 0, 'pages' => 1]); exit;
            }
        }

        if ($status !== '') {
            if (in_array($status, ['Draft', 'Archived'], true)) {
                $where[] = "COALESCE(p.status, '') = :statusExact";
                $args[':statusExact'] = $status;
            } else {
                if ($status === 'Out of Stock') {
                    $where[] = "p.stock = 0";
                } elseif ($status === 'Low Stock') {
                    $where[] = "p.stock > 0 AND p.stock <= 10";
                } elseif ($status === 'In Stock') {
                    $where[] = "p.stock > 10";
                }
            }
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count
        $stCount = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
        foreach ($args as $k => $v) $stCount->bindValue($k, $v);
        $stCount->execute();
        $total = (int)$stCount->fetchColumn();

        // Data
        $sql = "SELECT p.* FROM products p $whereSql ORDER BY p.id DESC LIMIT :lim OFFSET :off";
        $st  = $pdo->prepare($sql);
        foreach ($args as $k => $v) $st->bindValue($k, $v);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();

        // Enrich
        $out = [];
        foreach ($rows as $r) {
            $calc = auto_status_from_stock((int)$r['stock']);
            persist_auto_status_if_needed($pdo, (int)$r['id'], $r['status'] ?? null, (int)$r['stock'], $calc);

            $r['status']      = $r['status'] ?: $calc;
            $r['sizes']       = fetch_sizes($pdo, (int)$r['id']);
            $r['collections'] = fetch_collections_str($pdo, (int)$r['id']);
            $r['type']        = fetch_type($pdo, (int)$r['id']);
            $r['fabrics']     = fetch_fabrics($pdo, (int)$r['id']);
            $r['colors']      = fetch_colors($pdo, (int)$r['id']);

            $out[] = $r;
        }

        echo json_encode([
            'products' => $out,
            'total'    => $total,
            'pages'    => max(1, (int)ceil($total / $limit))
        ]);
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        $msg = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : 'Server error';
        if (defined('APP_DEBUG') && APP_DEBUG) error_log('[products_api GET] ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}



/* ===========================
   POST (create or update)
   =========================== */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJson = stripos($contentType, 'application/json') !== false;
    $body = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

    $id = isset($body['id']) ? (int)$body['id'] : null;
    $name = trim((string)($body['name'] ?? ''));
    $sku = trim((string)($body['sku'] ?? ''));
    $price = (float)($body['price'] ?? 0);
    $stock = max(0, (int)($body['stock'] ?? 0));
    $desc = (string)($body['description'] ?? '');
    $status = trim((string)($body['status'] ?? 'Auto (by stock)'));
    $image = trim((string)($body['image'] ?? ''));
    $sizes = (array)($body['sizes'] ?? []);
    $collectionName = trim((string)($body['collection'] ?? ''));
    $gallery = (array)($body['gallery'] ?? []);
    $categoryId = (int)($body['category_id'] ?? 0);
    $type = trim((string)($body['type'] ?? ''));
    $fabrics = (array)($body['fabrics'] ?? []);
    $colors = (array)($body['colors'] ?? []);

    if ($name === '' || $sku === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'name and sku are required']);
        exit;
    }

    if ($status === '' || strtolower($status) === 'auto (by stock)' || strtolower($status) === 'auto') {
        $status = auto_status_from_stock($stock);
    }

    $pdo->beginTransaction();
    try {
        if ($type !== '') {
            $typeId = $pdo->query("SELECT id FROM product_types WHERE name=" . $pdo->quote($type) . " LIMIT 1")->fetchColumn();
        } else {
            $typeId = null;
        }

        if ($id) {
            $sql = "UPDATE products SET name=:name, sku=:sku, price=:price, stock=:stock, description=:d, status=:st, image_main_url=:img, type_id=:type_id WHERE id=:id";
            $st = $pdo->prepare($sql);
            $st->execute([
                ':name' => $name,
                ':sku' => $sku,
                ':price' => $price,
                ':stock' => $stock,
                ':d' => $desc,
                ':st' => $status,
                ':img' => $image,
                ':type_id' => $typeId,
                ':id' => $id
            ]);

            $pdo->prepare("DELETE FROM product_sizes WHERE product_id=?")->execute([$id]);
            if ($sizes) {
                $ins = $pdo->prepare("INSERT INTO product_sizes(product_id,size_label) VALUES(?,?)");
                foreach ($sizes as $s) {
                    $s = trim((string)$s);
                    if ($s === '') continue;
                    $ins->execute([$id, $s]);
                }
            }

            $pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$id]);
            if ($gallery) {
                $ins = $pdo->prepare("INSERT INTO product_images (product_id, image_url, alt_text, sort_order) VALUES (?, ?, ?, ?)");
                $order = 0;
                foreach ($gallery as $g) {
                    if (is_string($g)) {
                        $url = trim($g);
                        $alt = '';
                        $sort = $order++;
                    } else {
                        $url = trim((string)($g['url'] ?? ''));
                        $alt = trim((string)($g['alt'] ?? ''));
                        $sort = (int)($g['sort'] ?? $order++);
                    }
                    if ($url !== '') $ins->execute([$id, $url, $alt, $sort]);
                }
            }

            $pdo->prepare("DELETE FROM product_categories WHERE product_id=?")->execute([$id]);
            if ($categoryId > 0) {
                $pdo->prepare("INSERT INTO product_categories(product_id,category_id) VALUES(?,?)")->execute([$id, $categoryId]);
            } elseif ($collectionName !== '') {
                $cid = (int)$pdo->query("SELECT id FROM categories WHERE name=" . $pdo->quote($collectionName) . " LIMIT 1")->fetchColumn();
                if ($cid) {
                    $pdo->prepare("INSERT INTO product_categories(product_id,category_id) VALUES(?,?)")->execute([$id, $cid]);
                }
            }

            $pdo->prepare("DELETE FROM product_fabrics WHERE product_id=?")->execute([$id]);
            if ($fabrics) {
                $stmt = $pdo->prepare("SELECT id FROM fabric_options WHERE name=?");
                $ins = $pdo->prepare("INSERT INTO product_fabrics (product_id, fabric_id) VALUES (?, ?)");
                foreach ($fabrics as $fab) {
                    $stmt->execute([$fab]);
                    $fid = $stmt->fetchColumn();
                    if ($fid) $ins->execute([$id, $fid]);
                }
            }

            $pdo->prepare("DELETE FROM product_colors WHERE product_id=?")->execute([$id]);
            if ($colors) {
                $ins = $pdo->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
                foreach ($colors as $col) {
                    $cid = get_or_create_color_id($pdo, $col);
                    if ($cid) $ins->execute([$id, $cid]);
                }
            }


            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $id]);
            exit;
        } else {
            $slug = strtolower(trim(preg_replace('~[^a-z0-9]+~i', '-', $name), '-'));
            $sql = "INSERT INTO products (name, slug, sku, description, price, stock, status, image_main_url, type_id) VALUES (:name, :slug, :sku, :d, :price, :stock, :st, :img, :type_id)";
            $st = $pdo->prepare($sql);
            $st->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':sku' => $sku,
                ':d' => $desc,
                ':price' => $price,
                ':stock' => $stock,
                ':st' => $status,
                ':img' => $image,
                ':type_id' => $typeId
            ]);
            $newId = (int)$pdo->lastInsertId();

            if ($sizes) {
                $ins = $pdo->prepare("INSERT INTO product_sizes(product_id,size_label) VALUES(?,?)");
                foreach ($sizes as $s) {
                    $s = trim((string)$s);
                    if ($s === '') continue;
                    $ins->execute([$newId, $s]);
                }
            }

            if ($gallery) {
                $ins = $pdo->prepare("INSERT INTO product_images (product_id, image_url, alt_text, sort_order) VALUES (?, ?, ?, ?)");
                $order = 0;
                foreach ($gallery as $g) {
                    if (is_string($g)) {
                        $url = trim($g);
                        $alt = '';
                        $sort = $order++;
                    } else {
                        $url = trim((string)($g['url'] ?? ''));
                        $alt = trim((string)($g['alt'] ?? ''));
                        $sort = (int)($g['sort'] ?? $order++);
                    }
                    if ($url !== '') $ins->execute([$newId, $url, $alt, $sort]);
                }
            }

            if ($categoryId > 0) {
                $pdo->prepare("INSERT INTO product_categories(product_id,category_id) VALUES(?,?)")->execute([$newId, $categoryId]);
            } elseif ($collectionName !== '') {
                $cid = (int)$pdo->query("SELECT id FROM categories WHERE name=" . $pdo->quote($collectionName) . " LIMIT 1")->fetchColumn();
                if ($cid) {
                    $pdo->prepare("INSERT INTO product_categories(product_id,category_id) VALUES(?,?)")->execute([$newId, $cid]);
                }
            }

            if ($fabrics) {
                $stmt = $pdo->prepare("SELECT id FROM fabric_options WHERE name=?");
                $ins = $pdo->prepare("INSERT INTO product_fabrics (product_id, fabric_id) VALUES (?, ?)");
                foreach ($fabrics as $fab) {
                    $stmt->execute([$fab]);
                    $fid = $stmt->fetchColumn();
                    if ($fid) $ins->execute([$newId, $fid]);
                }
            }

            if ($colors) {
                $stmt = $pdo->prepare("SELECT id FROM color_options WHERE name=?");
                $ins = $pdo->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
                foreach ($colors as $col) {
                    $stmt->execute([$col]);
                    $cid = $stmt->fetchColumn();
                    if ($cid) $ins->execute([$newId, $cid]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $newId]);
            exit;
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        $msg = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : 'database error';
        if (defined('APP_DEBUG') && APP_DEBUG) error_log('[products_api POST] ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}


/* ===========================
   DELETE
   =========================== */
// ===========================
// DELETE
// ===========================
if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = isset($q['id']) ? (int)$q['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'bad id']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM product_sizes WHERE product_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_categories WHERE product_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_fabrics WHERE product_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_colors WHERE product_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        $msg = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : 'database error';
        if (defined('APP_DEBUG') && APP_DEBUG) error_log('[products_api DELETE] ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
}


http_response_code(405);
echo json_encode(['success'=>false,'error'=>'Method not allowed']);
