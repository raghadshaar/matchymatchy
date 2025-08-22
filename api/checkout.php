<?php
// /matchymatchy/api/checkout.php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../PHP/db.php';
session_start();

/**
 * نفترض إنه الـ user_id محفوظ بالجلسة (اللي عملته سابقاً مع cart.php)
 * تأكدي إن تسجيل الدخول يضع $_SESSION['user_id'].
 */
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok'=>false, 'error'=>'Not authenticated']);
    exit;
}

try {
    // 1) نجيب cart_id للمستخدم وعناصرها
    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_id = $stmt->fetchColumn();

    if (!$cart_id) {
        echo json_encode(['ok'=>false, 'error'=>'Cart is empty']);
        exit;
    }

    $sqlItems = "SELECT ci.product_id, ci.size, ci.quantity, ci.unit_price,
                      p.name AS product_name
               FROM cart_items ci
               LEFT JOIN products p ON p.id = ci.product_id
               WHERE ci.cart_id = ?";
    $stmt = $pdo->prepare($sqlItems);
    $stmt->execute([$cart_id]);
    $items = $stmt->fetchAll();

    if (!$items) {
        echo json_encode(['ok'=>false, 'error'=>'Cart has no items']);
        exit;
    }

    // 2) نحسب المبالغ
    $subtotal = 0.00;
    foreach ($items as $it) {
        $subtotal += (float)$it['unit_price'] * (int)$it['quantity'];
    }
    $shipping = ($subtotal > 0) ? 15.00 : 0.00;       // نفس منطق صفحة الكارت
    $tax      = round(($subtotal) * 0.17, 2);         // لو عندك ضريبة 17%
    $total    = round($subtotal + $shipping + $tax, 2);

    // 3) نجيب بيانات العميل من users (أو ضعيها يدوياً إن لسا ما عندك عنوان)
    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id=?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch() ?: ['first_name'=>'','last_name'=>'','email'=>''];

    $customer_name  = trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? '')) ?: 'Customer';
    $customer_email = $u['email'] ?? 'unknown@example.com';
    $customer_phone = null;       // عدّلي لو عندك حقول
    $customer_addr  = null;       // عدّلي لو عندك حقول

    // 4) نبدأ ترانزاكشن وننشئ الطلب
    $pdo->beginTransaction();

    $insOrder = "INSERT INTO orders
      (public_id, user_id, customer_name, customer_email, customer_phone, customer_address,
       subtotal, shipping, tax, total, payment_status, order_status)
    VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending')";
    $stmt = $pdo->prepare($insOrder);
    $stmt->execute([
        $user_id, $customer_name, $customer_email, $customer_phone, $customer_addr,
        $subtotal, $shipping, $tax, $total
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // نبني public_id مثل ORD-YYYY-000123
    $public = 'ORD-'.date('Y').'-'.str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE orders SET public_id=? WHERE id=?")->execute([$public, $orderId]);

    // 5) نضيف العناصر
    $insItem = $pdo->prepare("INSERT INTO order_items
      (order_id, product_id, product_name, size, unit_price, quantity, line_total)
      VALUES (?,?,?,?,?,?,?)");
    foreach ($items as $it) {
        $unit  = (float)$it['unit_price'];
        $qty   = (int)$it['quantity'];
        $line  = round($unit * $qty, 2);
        $name  = $it['product_name'] ?: 'Product #'.$it['product_id'];
        $insItem->execute([
            $orderId,
            $it['product_id'],
            $name,
            $it['size'] ?? '',
            $unit,
            $qty,
            $line
        ]);
    }

    // 6) نفرغ السلة
    $pdo->prepare("DELETE FROM cart_items WHERE cart_id=?")->execute([$cart_id]);
    // احتفاط بسجل cart نفسه للمستخدم (أحسن)، لو بدك تحذفيه كليًّا:
    // $pdo->prepare("DELETE FROM carts WHERE id=?")->execute([$cart_id]);

    $pdo->commit();

    echo json_encode(['ok'=>true, 'order_public_id'=>$public, 'order_id'=>$orderId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'Checkout failed', 'detail'=>$e->getMessage()]);
}
