<?php
declare(strict_types=1);
require __DIR__ . '/../config.php';  // must return mysqli via db()
session_start();
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* Error -> JSON */
set_error_handler(function($no,$str,$file,$line){ http_response_code(500); echo json_encode(['error'=>'PHP error','detail'=>"$str @ $file:$line"]); exit; });
set_exception_handler(function(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'Exception','detail'=>$e->getMessage()]); exit; });

function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw==='') return [];
    $d = json_decode($raw,true);
    if ($d===null && json_last_error()!==JSON_ERROR_NONE) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }
    return $d ?? [];
}

$conn = db();

/* ---- Config ---- */
const SHIPPING_FLAT = 15.00;
const VAT_RATE = 0.17;
function money(float $n): float { return round($n, 2); }
function now(): string { return date('Y-m-d H:i:s'); }

/* ---- Cart helpers ---- */
function get_or_create_cart(mysqli $conn): array {
    $sid = session_id();
    $stmt = $conn->prepare("SELECT * FROM carts WHERE session_id=?");
    $stmt->bind_param('s',$sid); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($row) return $row;

    $ins = $conn->prepare("INSERT INTO carts (session_id) VALUES (?)");
    $ins->bind_param('s',$sid); $ins->execute();
    $id = $ins->insert_id; $ins->close();
    return ['id'=>$id,'session_id'=>$sid,'user_id'=>null,'coupon_id'=>null];
}

function product_for_cart(mysqli $conn, int $productId): array {
    $sql = "SELECT id, name, price, currency, stock, status, image_main_url FROM products WHERE id=? LIMIT 1";
    $st = $conn->prepare($sql); $st->bind_param('i',$productId); $st->execute();
    $p = $st->get_result()->fetch_assoc(); $st->close();
    if (!$p) { http_response_code(404); echo json_encode(['error'=>'Product not found']); exit; }

    // availability rules
    $status = $p['status'];
    if ($status === 'auto') {
        $status = ((int)$p['stock'] > 0) ? ((int)$p['stock'] <= 3 ? 'low_stock' : 'in_stock') : 'out_of_stock';
    }
    if (in_array($status, ['draft','archived','out_of_stock'], true)) {
        http_response_code(400); echo json_encode(['error'=>'Product not available']); exit;
    }
    return [
        'id'        => (int)$p['id'],
        'name'      => $p['name'],
        'price'     => (float)$p['price'],
        'currency'  => $p['currency'] ?: 'ILS',
        'stock'     => (int)$p['stock'],
        'image'     => $p['image_main_url']
    ];
}

function cart_items_payload(mysqli $conn, int $cartId): array {
    $sql = "SELECT ci.product_id, ci.unit_price, ci.unit_currency, ci.quantity, ci.line_total,
                 p.name, p.image_main_url AS image
          FROM cart_items ci
          JOIN products p ON p.id=ci.product_id
          WHERE ci.cart_id=?
          ORDER BY ci.id ASC";
    $st = $conn->prepare($sql); $st->bind_param('i',$cartId); $st->execute();
    $items = [];
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
        $items[] = [
            'id'         => (int)$r['product_id'],
            'name'       => $r['name'],
            'image'      => $r['image'],
            'price'      => (float)$r['unit_price'],
            'currency'   => $r['unit_currency'],
            'quantity'   => (int)$r['quantity'],
            'line_total' => (float)$r['line_total']
        ];
    }
    $st->close();
    return $items;
}

function compute_totals(mysqli $conn, int $cartId): array {
    $st = $conn->prepare("SELECT SUM(line_total) AS subtotal FROM cart_items WHERE cart_id=?");
    $st->bind_param('i',$cartId); $st->execute();
    $subtotal = (float)($st->get_result()->fetch_assoc()['subtotal'] ?? 0); $st->close();

    // coupon
    $st = $conn->prepare("SELECT coupon_id FROM carts WHERE id=?");
    $st->bind_param('i',$cartId); $st->execute();
    $cid = $st->get_result()->fetch_assoc()['coupon_id'] ?? null; $st->close();

    $discount = 0.0; $coupon = null;
    if ($cid) {
        $q = $conn->prepare("SELECT id,code,type,amount,min_subtotal,starts_at,ends_at,active FROM coupons WHERE id=?");
        $q->bind_param('i',$cid); $q->execute(); $c = $q->get_result()->fetch_assoc(); $q->close();
        $okDates = (!$c['starts_at'] || $c['starts_at'] <= now()) && (!$c['ends_at'] || $c['ends_at'] >= now());
        if ($c && (int)$c['active']===1 && $okDates && $subtotal >= (float)$c['min_subtotal']) {
            if ($c['type']==='percentage') $discount = $subtotal * (float)$c['amount'];
            else                            $discount = (float)$c['amount'];
            $discount = min($discount, $subtotal);
            $coupon = ['code'=>$c['code'],'type'=>$c['type'],'amount'=>(float)$c['amount']];
        } else {
            $upd = $conn->prepare("UPDATE carts SET coupon_id=NULL WHERE id=?");
            $upd->bind_param('i',$cartId); $upd->execute(); $upd->close();
        }
    }

    $shipping = $subtotal > 0 ? SHIPPING_FLAT : 0.0;
    $taxable  = max(0.0, $subtotal - $discount);
    $tax      = $taxable * VAT_RATE;
    $total    = $taxable + $shipping + $tax;

    // NOTE: currency — we use products.currency; if you ever mix currencies in a cart,
    // convert before inserting lines (beyond scope here).
    return [
        'subtotal' => money($subtotal),
        'discount' => money($discount),
        'shipping' => money($shipping),
        'tax'      => money($tax),
        'total'    => money($total),
        'currency' => 'ILS',
        'coupon'   => $coupon
    ];
}

/* ---- Routing ---- */
$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

if ($action === 'get') {
    $cart = get_or_create_cart($conn);
    echo json_encode([
        'items'  => cart_items_payload($conn, (int)$cart['id']),
        'totals' => compute_totals($conn, (int)$cart['id'])
    ]);
    exit;
}

if ($action === 'add') {
    $in  = json_input();
    $pid = (int)($in['product_id'] ?? 0);
    $qty = max(1, (int)($in['qty'] ?? 1));
    if ($pid<=0) { http_response_code(400); echo json_encode(['error'=>'product_id required']); exit; }

    $cart = get_or_create_cart($conn);
    $p    = product_for_cart($conn, $pid);

    if ($p['stock'] < $qty) { http_response_code(400); echo json_encode(['error'=>'Insufficient stock']); exit; }

    $conn->begin_transaction();
    try {
        // merge if same product already in cart
        $sel = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id=? AND product_id=?");
        $sel->bind_param('ii', $cart['id'], $pid); $sel->execute();
        $row = $sel->get_result()->fetch_assoc(); $sel->close();

        if ($row) {
            $newQty = $row ? (int)$row['quantity'] + $qty : $qty;
            if ($newQty > $p['stock']) { throw new Exception('Insufficient stock'); }
            $line = money($p['price'] * $newQty);
            $upd = $conn->prepare("UPDATE cart_items SET quantity=?, unit_price=?, unit_currency=?, line_total=? WHERE id=?");
            $upd->bind_param('idsdi', $newQty, $p['price'], $p['currency'], $line, $row['id']);
            $upd->execute(); $upd->close();
        } else {
            $line = money($p['price'] * $qty);
            $ins = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, unit_price, unit_currency, quantity, line_total)
                             VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param('iisdid', $cart['id'], $pid, $p['price'], $p['currency'], $qty, $line);
            $ins->execute(); $ins->close();
        }
        $conn->commit();
    } catch (Throwable $e) { $conn->rollback(); http_response_code(400); echo json_encode(['error'=>$e->getMessage()]); exit; }

    echo json_encode(['added'=>true,
        'items'=>cart_items_payload($conn,(int)$cart['id']),
        'totals'=>compute_totals($conn,(int)$cart['id'])
    ]); exit;
}

if ($action === 'update') {
    $in  = json_input();
    $pid = (int)($in['product_id'] ?? 0);
    $qty = max(0, (int)($in['qty'] ?? 0));
    if ($pid<=0) { http_response_code(400); echo json_encode(['error'=>'product_id required']); exit; }
    $cart = get_or_create_cart($conn);

    // find line
    $sel = $conn->prepare("SELECT id FROM cart_items WHERE cart_id=? AND product_id=?");
    $sel->bind_param('ii', $cart['id'], $pid); $sel->execute();
    $row = $sel->get_result()->fetch_assoc(); $sel->close();
    if (!$row) { http_response_code(404); echo json_encode(['error'=>'Item not in cart']); exit; }

    if ($qty === 0) {
        $del = $conn->prepare("DELETE FROM cart_items WHERE id=?");
        $del->bind_param('i',$row['id']); $del->execute(); $del->close();
    } else {
        $p = product_for_cart($conn, $pid);
        if ($qty > $p['stock']) { http_response_code(400); echo json_encode(['error'=>'Insufficient stock']); exit; }
        $line = money($p['price'] * $qty);
        $upd = $conn->prepare("UPDATE cart_items SET quantity=?, unit_price=?, unit_currency=?, line_total=? WHERE id=?");
        $upd->bind_param('idsdi', $qty, $p['price'], $p['currency'], $line, $row['id']);
        $upd->execute(); $upd->close();
    }

    echo json_encode(['updated'=>true,
        'items'=>cart_items_payload($conn,(int)$cart['id']),
        'totals'=>compute_totals($conn,(int)$cart['id'])
    ]); exit;
}

if ($action === 'remove') {
    $in  = json_input();
    $pid = (int)($in['product_id'] ?? 0);
    if ($pid<=0) { http_response_code(400); echo json_encode(['error'=>'product_id required']); exit; }
    $cart = get_or_create_cart($conn);
    $del = $conn->prepare("DELETE FROM cart_items WHERE cart_id=? AND product_id=?");
    $del->bind_param('ii',$cart['id'],$pid); $del->execute(); $del->close();
    echo json_encode(['removed'=>true,
        'items'=>cart_items_payload($conn,(int)$cart['id']),
        'totals'=>compute_totals($conn,(int)$cart['id'])
    ]); exit;
}

if ($action === 'clear') {
    $cart = get_or_create_cart($conn);
    $conn->query("DELETE FROM cart_items WHERE cart_id=".(int)$cart['id']);
    $conn->query("UPDATE carts SET coupon_id=NULL WHERE id=".(int)$cart['id']);
    echo json_encode(['cleared'=>true,'items'=>[],'totals'=>['subtotal'=>0,'discount'=>0,'shipping'=>0,'tax'=>0,'total'=>0,'currency'=>'ILS']]); exit;
}

if ($action === 'apply_coupon') {
    $in = json_input(); $code = strtoupper(trim((string)($in['code'] ?? '')));
    if ($code===''){ http_response_code(400); echo json_encode(['error'=>'code required']); exit; }
    $cart = get_or_create_cart($conn);

    $q = $conn->prepare("SELECT * FROM coupons WHERE UPPER(code)=? LIMIT 1");
    $q->bind_param('s',$code); $q->execute(); $c = $q->get_result()->fetch_assoc(); $q->close();
    if (!$c || (int)$c['active']!==1) { http_response_code(400); echo json_encode(['error'=>'Invalid coupon']); exit; }
    $t = now();
    if (($c['starts_at'] && $c['starts_at']>$t) || ($c['ends_at'] && $c['ends_at']<$t)) {
        http_response_code(400); echo json_encode(['error'=>'Coupon not active']); exit;
    }

    $upd = $conn->prepare("UPDATE carts SET coupon_id=? WHERE id=?");
    $upd->bind_param('ii',$c['id'],$cart['id']); $upd->execute(); $upd->close();

    echo json_encode(['applied'=>true,
        'coupon'=>$c['code'],
        'items'=>cart_items_payload($conn,(int)$cart['id']),
        'totals'=>compute_totals($conn,(int)$cart['id'])
    ]); exit;
}

if ($action === 'checkout') {
    // expects: customer {name,email,phone,address}
    $in = json_input();
    $customer = (array)($in['customer'] ?? []);
    $name  = trim((string)($customer['name'] ?? ''));
    $email = trim((string)($customer['email'] ?? ''));
    $phone = trim((string)($customer['phone'] ?? ''));
    $addr  = trim((string)($customer['address'] ?? ''));
    $pay   = (string)($in['payment_status'] ?? 'Pending');
    $stat  = (string)($in['order_status'] ?? 'Pending');

    if ($name==='' || $email===''){ http_response_code(400); echo json_encode(['error'=>'Name and email are required']); exit; }

    $cart = get_or_create_cart($conn);
    $items = cart_items_payload($conn, (int)$cart['id']);
    if (!$items){ http_response_code(400); echo json_encode(['error'=>'Cart is empty']); exit; }

    $tot = compute_totals($conn, (int)$cart['id']);

    $conn->begin_transaction();
    try {
        // Re-validate stock and lock rows
        foreach ($items as $it) {
            $lock = $conn->prepare("SELECT stock FROM products WHERE id=? FOR UPDATE");
            $lock->bind_param('i',$it['id']); $lock->execute();
            $stock = (int)$lock->get_result()->fetch_assoc()['stock']; $lock->close();
            if ($stock < $it['quantity']) { throw new Exception('Insufficient stock for product ID '.$it['id']); }
        }

        // Create order
        $ins = $conn->prepare("INSERT INTO orders (public_id, order_date, customer_name, customer_email, customer_phone, customer_address, subtotal, shipping, tax, total, currency, payment_status, order_status) VALUES ('', NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 'ILS', ?, ?)");
        $ins->bind_param('ssssdddsss', $name,$email,$phone,$addr, $tot['subtotal'],$tot['shipping'],$tot['tax'],$tot['total'], $pay,$stat);
        $ins->execute(); $orderId = $ins->insert_id; $ins->close();

        $pub = sprintf('ORD-%s-%03d', date('Y'), $orderId);
        $up  = $conn->prepare("UPDATE orders SET public_id=? WHERE id=?");
        $up->bind_param('si',$pub,$orderId); $up->execute(); $up->close();

        // Insert items + decrement stock
        $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_currency, quantity, line_total) VALUES (?, ?, ?, ?, 'ILS', ?, ?)");
        $dec = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id=?");

        foreach ($items as $it) {
            $oi->bind_param('iisdid', $orderId, $it['id'], $it['name'], $it['price'], $it['quantity'], $it['line_total']);
            $oi->execute();
            $dec->bind_param('ii', $it['quantity'], $it['id']); $dec->execute();
        }
        $oi->close(); $dec->close();

        // Clear cart
        $conn->query("DELETE FROM cart_items WHERE cart_id=".(int)$cart['id']);
        $conn->query("UPDATE carts SET coupon_id=NULL WHERE id=".(int)$cart['id']);

        $conn->commit();
        echo json_encode(['checked_out'=>true, 'public_id'=>$pub, 'totals'=>$tot]);
    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(400);
        echo json_encode(['error'=>$e->getMessage()]);
    }
    exit;
}

/* Fallback */
http_response_code(400);
echo json_encode(['error'=>'Unknown action']);
