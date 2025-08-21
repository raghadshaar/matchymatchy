<?php
declare(strict_types=1);

require __DIR__ . '/../../config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* === Always return JSON on PHP errors (helps your UI) === */
set_error_handler(function($errno, $errstr, $file, $line){
    http_response_code(500);
    echo json_encode(['error' => 'PHP error', 'detail' => "$errstr @ $file:$line"]);
    exit;
});
set_exception_handler(function(Throwable $e){
    http_response_code(500);
    echo json_encode(['error' => 'Unhandled exception', 'detail' => $e->getMessage()]);
    exit;
});

function require_admin(): void {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
require_admin();

function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    return $data ?? [];
}

function ok($data, int $status=200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$conn = db(); // from your config.php

$action = $_GET['action'] ?? 'list';

/* ====== helpers ====== */
const PAYMENTS = ['Paid','Pending','Failed','Refunded','COD'];
const STATUSES = ['Pending','Processing','Shipped','Delivered','Cancelled'];

function is_public_id(string $v): bool {
    return (bool)preg_match('/^ORD-\d{4}-\d{3,}$/', $v);
}
function sanitize_sort_by(string $k): string {
    $map = [
        'id'       => 'o.id',
        'date'     => 'o.order_date',
        'total'    => 'o.total',
        'customer' => 'o.customer_name',
        'status'   => 'o.order_status',
        'payment'  => 'o.payment_status',
    ];
    return $map[$k] ?? 'o.order_date';
}
function sanitize_dir(string $d): string {
    return strtolower($d) === 'asc' ? 'ASC' : 'DESC';
}
function clamp_int($v, int $min, int $max): int {
    $i = (int)$v; return max($min, min($max, $i));
}
function generate_public_id(int $id): string {
    return sprintf('ORD-%s-%03d', date('Y'), $id);
}

/* ====== actions ====== */

if ($action === 'list') {
    $q        = trim((string)($_GET['q'] ?? ''));
    $status   = trim((string)($_GET['status'] ?? ''));
    $payment  = trim((string)($_GET['payment'] ?? ''));
    $page     = clamp_int($_GET['page'] ?? 1, 1, 100000);
    $pageSize = clamp_int($_GET['page_size'] ?? 8, 1, 100);
    $sortBy   = sanitize_sort_by((string)($_GET['sort_by'] ?? 'date'));
    $sortDir  = sanitize_dir((string)($_GET['sort_dir'] ?? 'desc'));

    $where = [];
    $bind = [];
    $types = '';

    if ($q !== '') {
        $where[] = '(o.public_id LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)';
        $like = "%$q%";
        $bind[] = $like; $types .= 's';
        $bind[] = $like; $types .= 's';
        $bind[] = $like; $types .= 's';
    }
    if ($status !== '') {
        $where[] = 'o.order_status = ?';
        $bind[] = $status; $types .= 's';
    }
    if ($payment !== '') {
        $where[] = 'o.payment_status = ?';
        $bind[] = $payment; $types .= 's';
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Query page
    $offset = ($page - 1) * $pageSize;
    $sql = "
      SELECT o.id, o.public_id, o.order_date, o.total, o.payment_status, o.order_status,
             o.customer_name, o.customer_email
      FROM orders o
      $whereSql
      ORDER BY $sortBy $sortDir
      LIMIT $pageSize OFFSET $offset
    ";
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$bind);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Total count (avoid deprecated SQL_CALC_FOUND_ROWS)
    $sqlCount = "SELECT COUNT(*) AS c FROM orders o $whereSql";
    $stmt = $conn->prepare($sqlCount);
    if ($types) $stmt->bind_param($types, ...$bind);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    ok([
        'data' => array_map(function($r){
            $r['date'] = $r['order_date']; unset($r['order_date']);
            return $r;
        }, $rows),
        'meta' => [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'pages' => (int)ceil($total / $pageSize),
        ]
    ]);
}

if ($action === 'get') {
    $idParam = $_GET['id'] ?? '';
    if ($idParam === '') { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }

    if (is_public_id($idParam)) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE public_id=?");
        $stmt->bind_param('s', $idParam);
    } else {
        $id = (int)$idParam;
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
        $stmt->bind_param('i', $id);
    }
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) { http_response_code(404); echo json_encode(['error'=>'Order not found']); exit; }

    $stmt = $conn->prepare("SELECT id, product_id, product_name AS name, unit_price AS price, quantity AS qty, line_total AS subtotal
                              FROM order_items WHERE order_id=? ORDER BY id ASC");
    $stmt->bind_param('i', $order['id']);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    ok(['order' => $order, 'items' => $items]);
}

if ($action === 'create') {
    $in = json_input();

    // basic validation
    $customer = $in['customer'] ?? [];
    $items    = $in['items'] ?? [];
    $shipping = (float)($in['shipping'] ?? 0);
    $tax      = (float)($in['tax'] ?? 0);
    $payment  = $in['payment_status'] ?? 'Pending';
    $status   = $in['order_status'] ?? 'Pending';

    if (!$customer || !($items && is_array($items))) {
        http_response_code(400);
        echo json_encode(['error'=>'customer and items[] are required']); exit;
    }
    if (!in_array($payment, PAYMENTS, true) || !in_array($status, STATUSES, true)) {
        http_response_code(400);
        echo json_encode(['error'=>'invalid payment_status or order_status']); exit;
    }

    $conn->begin_transaction(); // START TRANSACTION

    try {
        $stmt = $conn->prepare("
          INSERT INTO orders (public_id, customer_name, customer_email, customer_phone, customer_address,
                              shipping, tax, payment_status, order_status, subtotal, total)
          VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)
        ");
        $name = (string)($customer['name'] ?? '');
        $email = (string)($customer['email'] ?? '');
        $phone = (string)($customer['phone'] ?? '');
        $addr  = (string)($customer['address'] ?? '');
        $stmt->bind_param('ssssddss', $name, $email, $phone, $addr, $shipping, $tax, $payment, $status);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();

        // items
        $subtotal = 0.0;
        $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                               VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($items as $it) {
            $pid  = isset($it['product_id']) ? (int)$it['product_id'] : null;
            $name = (string)($it['name'] ?? ($it['product_name'] ?? ''));
            $price= (float)($it['price'] ?? $it['unit_price'] ?? 0);
            $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
            $line = round($price * $qty, 2);
            $subtotal += $line;

            $ins->bind_param('iissid', $orderId, $pid, $name, $price, $qty, $line);
            $ins->execute();
        }
        $ins->close();

        $total = round($subtotal + $shipping + $tax, 2);
        $publicId = generate_public_id($orderId);

        $upd = $conn->prepare("UPDATE orders
                               SET public_id=?, subtotal=?, total=?
                               WHERE id=?");
        $upd->bind_param('sdsi', $publicId, $subtotal, $total, $orderId);
        $upd->execute();
        $upd->close();

        $conn->commit();

        ok(['created' => true, 'id' => $orderId, 'public_id' => $publicId], 201);

    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($action === 'update') {
    $in = json_input();
    $idParam = (string)($in['id'] ?? $in['public_id'] ?? '');
    if ($idParam === '') { http_response_code(400); echo json_encode(['error'=>'id or public_id required']); exit; }

    // resolve order id
    if (is_public_id($idParam)) {
        $stmt = $conn->prepare("SELECT id FROM orders WHERE public_id=?");
        $stmt->bind_param('s', $idParam);
    } else {
        $id = (int)$idParam;
        $stmt = $conn->prepare("SELECT id FROM orders WHERE id=?");
        $stmt->bind_param('i', $id);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) { http_response_code(404); echo json_encode(['error'=>'Order not found']); exit; }
    $orderId = (int)$row['id'];

    $conn->begin_transaction();
    try {
        // allowed updates
        $fields = [];
        $binds  = [];
        $types  = '';

        $map = [
            'customer_name'  => 's', 'customer_email' => 's',
            'customer_phone' => 's', 'customer_address' => 's',
            'shipping' => 'd', 'tax' => 'd',
            'payment_status' => 's', 'order_status' => 's'
        ];
        foreach ($map as $k => $t) {
            if (array_key_exists($k, $in)) {
                if ($k === 'payment_status' && !in_array($in[$k], PAYMENTS, true)) continue;
                if ($k === 'order_status'  && !in_array($in[$k], STATUSES, true)) continue;
                $fields[] = "$k=?";
                $binds[]  = $in[$k];
                $types   .= $t;
            }
        }

        // items replacement (optional)
        $recalc = false;
        if (isset($in['items']) && is_array($in['items'])) {
            // remove old
            $del = $conn->prepare("DELETE FROM order_items WHERE order_id=?");
            $del->bind_param('i', $orderId);
            $del->execute();
            $del->close();

            $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $subtotal = 0.0;
            foreach ($in['items'] as $it) {
                $pid  = isset($it['product_id']) ? (int)$it['product_id'] : null;
                $name = (string)($it['name'] ?? $it['product_name'] ?? '');
                $price= (float)($it['price'] ?? $it['unit_price'] ?? 0);
                $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
                $line = round($price * $qty, 2);
                $subtotal += $line;

                $ins->bind_param('iissid', $orderId, $pid, $name, $price, $qty, $line);
                $ins->execute();
            }
            $ins->close();
            // push subtotal into update set
            $fields[] = "subtotal=?";
            $binds[]  = $subtotal;
            $types   .= 'd';
            $recalc   = true;
        }

        if ($fields) {
            $sql = "UPDATE orders SET ".implode(',',$fields)." WHERE id=?";
            $types .= 'i';
            $binds[] = $orderId;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$binds);
            $stmt->execute();
            $stmt->close();
        }

        // recompute total (subtotal + shipping + tax)
        $rs = $conn->prepare("SELECT subtotal, shipping, tax FROM orders WHERE id=?");
        $rs->bind_param('i', $orderId);
        $rs->execute();
        $tot = $rs->get_result()->fetch_assoc();
        $rs->close();

        $total = round((float)$tot['subtotal'] + (float)$tot['shipping'] + (float)$tot['tax'], 2);
        $up2 = $conn->prepare("UPDATE orders SET total=? WHERE id=?");
        $up2->bind_param('di', $total, $orderId);
        $up2->execute();
        $up2->close();

        $conn->commit();
        ok(['updated' => true, 'id' => $orderId, 'total' => $total]);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($action === 'delete') {
    $in = json_input();
    $idParam = (string)($in['id'] ?? $in['public_id'] ?? '');
    if ($idParam === '') { http_response_code(400); echo json_encode(['error'=>'id or public_id required']); exit; }

    if (is_public_id($idParam)) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE public_id=?");
        $stmt->bind_param('s', $idParam);
    } else {
        $id = (int)$idParam;
        $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
        $stmt->bind_param('i', $id);
    }
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    ok(['deleted' => $affected > 0, 'affected' => $affected]);
}

/* Unknown action */
http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
