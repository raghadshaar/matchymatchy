<?php
declare(strict_types=1);

/**
 * /matchymatchy/api/cart.php
 *
 * JSON Cart API (per-user). Requires login: $_SESSION['user_id'] must be set.
 * Tables expected:
 *
 *  carts (
 *    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 *    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 *  );
 *
 *  cart_items (
 *    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *    cart_id BIGINT UNSIGNED NOT NULL,
 *    product_id BIGINT UNSIGNED NOT NULL,
 *    size VARCHAR(64) NOT NULL DEFAULT '',
 *    quantity INT UNSIGNED NOT NULL DEFAULT 1,
 *    unit_price DECIMAL(10,2) NOT NULL,
 *    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *    UNIQUE KEY uniq_cart_line (cart_id, product_id, size),
 *    CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE
 *  );
 *
 *  products (must contain at least id, name, price, image_main_url)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// --------- Config (adjust if you want) ----------
const SHIPPING_FLAT      = 15.00;   // ₪
const TAX_RATE           = 0.17;    // 17%
const REQUIRE_LOGIN      = true;    // return 401 if not logged in
const CSRF_HEADER        = 'HTTP_X_CSRF_TOKEN';
// Coupons (optional)
$COUPONS = [
    'KIDS10'    => ['type' => 'percentage', 'discount' => 0.10, 'description' => '10% off'],
    'SAVE20'    => ['type' => 'fixed',      'discount' => 20.00, 'description' => '₪20 off'],
    'WELCOME15' => ['type' => 'percentage', 'discount' => 0.15, 'description' => '15% off'],
];

// --------- DB bootstrap (use your PHP/db.php) ----------
$ROOT  = dirname(__DIR__); // .../matchymatchy
$paths = [$ROOT . '/PHP/db.php', $ROOT . '/lib/db.php', $ROOT . '/backend/db.php'];
$dbOk  = false;
foreach ($paths as $p) {
    if (is_file($p)) { require_once $p; $dbOk = true; break; }
}
if (!$dbOk || !isset($pdo)) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'DB bootstrap failed', 'tried'=>$paths]);
    exit;
}

// --------- Helpers ----------
function json_out(array $payload, int $code = 200): never {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function need_login(): void {
    if (REQUIRE_LOGIN && empty($_SESSION['user_id'])) {
        json_out(['ok'=>false, 'error'=>'login required'], 401);
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(20));
    }
    return $_SESSION['csrf'];
}
function check_csrf_for_post(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') return;
    $hdr = $_SERVER[CSRF_HEADER] ?? '';
    if ($hdr === '' || $hdr !== csrf_token()) {
        json_out(['ok'=>false, 'error'=>'invalid CSRF'], 419);
    }
}

function get_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function get_cart_id(PDO $pdo, int $userId): int {
    // ensure a cart row per user
    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id=?");
    $stmt->execute([$userId]);
    $id = (int)$stmt->fetchColumn();
    if ($id) return $id;

    $stmt = $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    return (int)$pdo->lastInsertId();
}


function load_coupon(PDO $pdo, string $code): ?array {
    $st = $pdo->prepare("
        SELECT code, type, amount, min_subtotal, starts_at, ends_at, active, max_uses, used_count
        FROM coupons
        WHERE code=? AND active=1
          AND (starts_at IS NULL OR starts_at <= NOW())
          AND (ends_at   IS NULL OR ends_at   >= NOW())
        LIMIT 1
    ");
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function normalize_coupon_for_summary(array $row, float $subtotal): array {
    // Convert your DB row to what compute_summary() expects
    // DB: type in ('percentage','fixed'); amount = (0.10 for 10%) or 20.00 for fixed.
    $disc = 0.0;
    if ($row['type'] === 'percentage') $disc = (float)$row['amount'];
    else                               $disc = (float)$row['amount'];
    return ['type' => $row['type'], 'discount' => $disc, 'code' => $row['code']];
}

function fetch_items(PDO $pdo, int $cartId): array {
    // join products to fetch name + image
    $sql = "SELECT ci.id, ci.product_id, ci.size, ci.quantity, ci.unit_price,
                   p.name AS product_name,
                   COALESCE(p.image_main_url, '') AS image
            FROM cart_items ci
            JOIN products p ON p.id = ci.product_id
            WHERE ci.cart_id = ?
            ORDER BY ci.id DESC";
    $st = $pdo->prepare($sql);
    $st->execute([$cartId]);
    $rows = $st->fetchAll();
    // normalize payload for frontend
    return array_map(function($r){
        return [
            'id'         => (int)$r['id'],
            'product_id' => (int)$r['product_id'],
            'name'       => $r['product_name'],
            'size'       => $r['size'],
            'quantity'   => (int)$r['quantity'],
            'unit_price' => (float)$r['unit_price'],
            'image'      => $r['image'],
        ];
    }, $rows ?: []);
}

function compute_summary(array $items, ?array $coupon = null): array {
    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += (float)$it['unit_price'] * (int)$it['quantity'];
    }
    $discount = 0.0;
    if ($coupon) {
        if ($coupon['type'] === 'percentage') $discount = $subtotal * (float)$coupon['discount'];
        else                                  $discount = (float)$coupon['discount'];
        $discount = max(0.0, min($discount, $subtotal)); // never negative or over subtotal
    }
    $shipping = count($items) ? SHIPPING_FLAT : 0.0;
    $taxBase  = max(0.0, $subtotal - $discount);
    $tax      = $taxBase * TAX_RATE;
    $total    = $taxBase + $shipping + $tax;

    return [
        'subtotal' => round($subtotal, 2),
        'discount' => round($discount, 2),
        'shipping' => round($shipping, 2),
        'tax'      => round($tax, 2),
        'total'    => round($total, 2),
    ];
}

function body_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?? '', true);
    return is_array($data) ? $data : [];
}

// --------- Router ----------
$action = strtolower((string)($_GET['action'] ?? ''));
if ($action === '') $action = 'list';

// For POST actions, require CSRF
check_csrf_for_post();

try {
    switch ($action) {
        case 'list': {
            need_login();
            $uid   = get_user_id();
            $cid   = get_cart_id($pdo, $uid);
            $items = fetch_items($pdo, $cid);
            $sum   = compute_summary($items);
            $sumCoupon = null;
            $cs = $pdo->prepare("SELECT coupon_code FROM carts WHERE id=? LIMIT 1");
            $cs->execute([$cid]);
            $cCode = trim((string)$cs->fetchColumn());
            if ($cCode !== '') {
                $row = load_coupon($pdo, $cCode);
                if ($row) {
                    // Check min_subtotal
                    $subtotal = 0.0; foreach ($items as $it) { $subtotal += (float)$it['unit_price'] * (int)$it['quantity']; }
                    if ($subtotal >= (float)$row['min_subtotal']) {
                        $sumCoupon = normalize_coupon_for_summary($row, $subtotal);
                    }
                }
            }
            $sum = compute_summary($items, $sumCoupon);
            json_out([
                'ok'=>true,
                'csrf'=>csrf_token(),
                'items'=>$items,
                'summary'=>$sum,
                'coupon'=> $sumCoupon ? ['code'=>$sumCoupon['code']] : null
            ]);
        }
        case 'count': {
            need_login();
            $uid = get_user_id();

            // ensure cart exists, then count items
            $cid = get_cart_id($pdo, $uid);
            $st  = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0)
        FROM cart_items ci
        WHERE ci.cart_id = ?
    ");
            $st->execute([$cid]);
            $count = (int) $st->fetchColumn();

            json_out(['ok' => true, 'count' => $count]);
        }

        case 'add': {
            need_login();
            $data = body_json();
            $pid  = (int)($data['product_id'] ?? 0);
            $qty  = (int)($data['quantity']   ?? 1);
            $size = trim((string)($data['size'] ?? ''));
            if ($pid <= 0 || $qty <= 0) json_out(['ok'=>false,'error'=>'invalid payload'], 400);
            if (strlen($size) > 64) $size = substr($size, 0, 64);

            // fetch product price to store snapshot
            $st = $pdo->prepare("SELECT price FROM products WHERE id=?");
            $st->execute([$pid]);
            $price = $st->fetchColumn();
            if ($price === false) json_out(['ok'=>false,'error'=>'product not found'], 404);

            $cid   = get_cart_id($pdo, get_user_id());

            // upsert by (cart_id, product_id, size)
            // first try update
            $st = $pdo->prepare("UPDATE cart_items
                                 SET quantity = quantity + ?
                                 WHERE cart_id=? AND product_id=? AND size=?");
            $st->execute([$qty, $cid, $pid, $size]);

            if ($st->rowCount() === 0) {
                $st = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, size, quantity, unit_price)
                                     VALUES (?, ?, ?, ?, ?)");
                $st->execute([$cid, $pid, $size, $qty, $price]);
            }

            $items = fetch_items($pdo, $cid);
            $sum   = compute_summary($items);
            json_out(['ok'=>true, 'items'=>$items, 'summary'=>$sum]);
        }

        case 'update': {
            need_login();
            $data = body_json();
            $itemId = (int)($data['item_id'] ?? 0);
            $qty    = (int)($data['quantity'] ?? 0);
            if ($itemId <= 0 || $qty < 0) json_out(['ok'=>false, 'error'=>'invalid payload'], 400);

            $cid = get_cart_id($pdo, get_user_id());

            if ($qty === 0) {
                $st = $pdo->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?");
                $st->execute([$itemId, $cid]);
            } else {
                $st = $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND cart_id=?");
                $st->execute([$qty, $itemId, $cid]);
            }

            $items = fetch_items($pdo, $cid);
            $sum   = compute_summary($items);
            json_out(['ok'=>true, 'items'=>$items, 'summary'=>$sum]);
        }

        case 'remove': {
            need_login();
            $data = body_json();
            $itemId = (int)($data['item_id'] ?? 0);
            if ($itemId <= 0) json_out(['ok'=>false, 'error'=>'invalid payload'], 400);

            $cid = get_cart_id($pdo, get_user_id());
            $st  = $pdo->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?");
            $st->execute([$itemId, $cid]);

            $items = fetch_items($pdo, $cid);
            $sum   = compute_summary($items);
            json_out(['ok'=>true, 'items'=>$items, 'summary'=>$sum]);
        }

        case 'clear': {
            need_login();
            $cid = get_cart_id($pdo, get_user_id());
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cid]);
            $items = [];
            $sum   = compute_summary($items);
            json_out(['ok'=>true, 'items'=>$items, 'summary'=>$sum]);
        }

        case 'applycoupon': {
            need_login();
            $data = body_json();
            $code = strtoupper(trim((string)($data['code'] ?? '')));
            if ($code === '') json_out(['ok'=>false,'error'=>'invalid coupon'], 400);

            $cid   = get_cart_id($pdo, get_user_id());
            $items = fetch_items($pdo, $cid);

            if (!count($items)) json_out(['ok'=>false,'error'=>'empty_cart'], 400);

            // Validate coupon against DB rules
            $row = load_coupon($pdo, $code);
            if (!$row) json_out(['ok'=>false,'error'=>'invalid_or_inactive_coupon'], 400);

            // compute subtotal to check min_subtotal
            $subtotal = 0.0; foreach ($items as $it) { $subtotal += (float)$it['unit_price'] * (int)$it['quantity']; }
            if ($subtotal < (float)$row['min_subtotal']) {
                json_out(['ok'=>false,'error'=>'subtotal_below_min'], 400);
            }

            // Persist choice on the cart (no decrement yet — do it on capture)
            $pdo->prepare("UPDATE carts SET coupon_code=? WHERE id=?")->execute([$code, $cid]);

            $sum = compute_summary($items, normalize_coupon_for_summary($row, $subtotal));
            json_out(['ok'=>true, 'coupon'=>['code'=>$code], 'items'=>$items, 'summary'=>$sum]);
        }


        default:
            json_out(['ok'=>false, 'error'=>'unknown action'], 400);
    }
} catch (Throwable $e) {
    // Never print HTML — always JSON
    json_out(['ok'=>false, 'error'=>'server error', 'detail'=>$e->getMessage()], 500);
}
