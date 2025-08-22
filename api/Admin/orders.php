<?php
// /matchymatchy/api/Admin/orders.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

try {
    // Adjust this path if your db.php lives somewhere else:
    require __DIR__ . '/../../PHP/db.php'; // defines $pdo (PDO to 'matchy_matchy')

    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $q       = trim($_GET['q'] ?? '');
        $status  = trim($_GET['status'] ?? '');
        $payment = trim($_GET['payment'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $psize   = max(1, (int)($_GET['page_size'] ?? 8));
        $offset  = ($page - 1) * $psize;

        // Build WHERE
        $where = [];
        $bind  = [];

        if ($q !== '') {
            $where[] = "(public_id LIKE :q OR customer_name LIKE :q OR customer_email LIKE :q)";
            $bind[':q'] = "%{$q}%";
        }
        if ($status !== '') {
            $where[] = "order_status = :status";
            $bind[':status'] = $status;
        }
        if ($payment !== '') {
            $where[] = "payment_status = :payment";
            $bind[':payment'] = $payment;
        }
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // Count
        $sqlCount = "SELECT COUNT(*) AS c FROM orders {$whereSql}";
        $stmt = $pdo->prepare($sqlCount);
        foreach ($bind as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $total = (int)($stmt->fetchColumn() ?: 0);
        $pages = max(1, (int)ceil($total / $psize));
        if ($page > $pages) { $page = $pages; $offset = ($page - 1) * $psize; }

        // Rows
        // NOTE: LIMIT/OFFSET must be integers; we inject after casting.
        $sql = "
          SELECT id, public_id, order_date, customer_name, customer_email,
                 total, payment_status, order_status
          FROM orders
          {$whereSql}
          ORDER BY order_date DESC, id DESC
          LIMIT {$psize} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        foreach ($bind as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        echo json_encode([
            'ok'   => true,
            'data' => [
                'rows'  => $rows,
                'page'  => $page,
                'pages' => $pages,
                'total' => $total
            ]
        ]);
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

        $stmt = $pdo->prepare("
            SELECT id, public_id, order_date, customer_name, customer_email, customer_phone, customer_address,
                   subtotal, shipping, tax, total, payment_status, order_status
            FROM orders WHERE id = :id
        ");
        $stmt->execute([':id'=>$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['ok'=>false,'error'=>'Order not found']); exit; }

        $stmt = $pdo->prepare("
            SELECT product_id, product_name, size, unit_price, quantity, line_total
            FROM order_items WHERE order_id = :id ORDER BY id ASC
        ");
        $stmt->execute([':id'=>$id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        echo json_encode(['ok'=>true, 'order'=>$order, 'items'=>$items]);
        exit;
    }

    if ($action === 'update') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

        // Editable fields from drawer:
        $customer_name    = trim($body['customer_name']    ?? '');
        $customer_email   = trim($body['customer_email']   ?? '');
        $customer_phone   = trim($body['customer_phone']   ?? '');
        $customer_address = trim($body['customer_address'] ?? '');
        $payment_status   = trim($body['payment_status']   ?? 'Pending');
        $order_status     = trim($body['order_status']     ?? 'Pending');
        $shipping         = (float)($body['shipping']      ?? 0);
        $tax              = (float)($body['tax']           ?? 0);

        // Recompute total = subtotal + shipping + tax (keep stored subtotal)
        $stmt = $pdo->prepare("SELECT COALESCE(subtotal,0) AS subtotal FROM orders WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        $subtotal = (float)($stmt->fetchColumn() ?? 0);
        $total    = $subtotal + $shipping + $tax;

        $stmt = $pdo->prepare("
            UPDATE orders SET
                customer_name    = :customer_name,
                customer_email   = :customer_email,
                customer_phone   = :customer_phone,
                customer_address = :customer_address,
                payment_status   = :payment_status,
                order_status     = :order_status,
                shipping         = :shipping,
                tax              = :tax,
                total            = :total
            WHERE id = :id
        ");
        $stmt->execute([
            ':customer_name'    => $customer_name,
            ':customer_email'   => $customer_email,
            ':customer_phone'   => $customer_phone,
            ':customer_address' => $customer_address,
            ':payment_status'   => $payment_status,
            ':order_status'     => $order_status,
            ':shipping'         => $shipping,
            ':tax'              => $tax,
            ':total'            => $total,
            ':id'               => $id,
        ]);

        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'delete') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

        // ON DELETE CASCADE will remove order_items if FK is set accordingly
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id=:id");
        $stmt->execute([':id'=>$id]);

        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'Server error', 'detail'=>$e->getMessage()]);
}
