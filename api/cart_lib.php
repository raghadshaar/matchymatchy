<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// نفس الثوابت اللي في cart.php (حدّثيها إن لزم)
const SHIPPING_FLAT = 15.00;
const TAX_RATE      = 0.17;

// استقدمي الـ PDO من db.php
$ROOT  = dirname(__DIR__); // .../matchymatchy
$paths = [$ROOT . '/PHP/db.php', $ROOT . '/lib/db.php', $ROOT . '/backend/db.php'];
foreach ($paths as $p) { if (is_file($p)) { require_once $p; break; } }

function clb_get_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }

function clb_get_cart_id(PDO $pdo, int $userId): int {
    $st=$pdo->prepare("SELECT id FROM carts WHERE user_id=?"); $st->execute([$userId]);
    $id=(int)$st->fetchColumn(); if ($id) return $id;
    $st=$pdo->prepare("INSERT INTO carts (user_id) VALUES (?)"); $st->execute([$userId]);
    return (int)$pdo->lastInsertId();
}

function clb_fetch_items(PDO $pdo, int $cartId): array {
    $sql="SELECT ci.id, ci.product_id, ci.size, ci.quantity, ci.unit_price,
                 p.name AS product_name, COALESCE(p.image_main_url,'') AS image
          FROM cart_items ci
          JOIN products p ON p.id=ci.product_id
          WHERE ci.cart_id=? ORDER BY ci.id DESC";
    $st=$pdo->prepare($sql); $st->execute([$cartId]);
    $rows=$st->fetchAll();
    return array_map(fn($r)=>[
        'id'=>(int)$r['id'],
        'product_id'=>(int)$r['product_id'],
        'name'=>$r['product_name'],
        'size'=>$r['size'],
        'quantity'=>(int)$r['quantity'],
        'unit_price'=>(float)$r['unit_price'],
        'image'=>$r['image'],
    ], $rows?:[]);
}

function clb_compute_summary(array $items): array {
    $subtotal=0.0; foreach ($items as $it) $subtotal += $it['unit_price']*$it['quantity'];
    $shipping = $items ? SHIPPING_FLAT : 0.0;
    $tax      = max(0.0,$subtotal)*TAX_RATE;
    $total    = $subtotal + $shipping + $tax;
    return [
        'subtotal'=>round($subtotal,2),
        'shipping'=>round($shipping,2),
        'tax'     =>round($tax,2),
        'total'   =>round($total,2),
    ];
}

/** ترجّع السلة كاملة للمستخدم الحالي */
function clb_compute_cart_for_current_user(): array {
    global $pdo;
    $uid = clb_get_user_id();
    $cid = clb_get_cart_id($pdo, $uid);
    $items = clb_fetch_items($pdo, $cid);
    $sum   = clb_compute_summary($items);
    return ['items'=>$items] + $sum;
}

