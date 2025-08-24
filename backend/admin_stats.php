<?php
// /backend/admin_stats.php
require_once __DIR__ . '/../PHP/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>'DB not initialized']); exit;
    }

    // Total Orders
    $total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // Revenue: إجمالي مبالغ الطلبات المدفوعة
    $revenue = (float)$pdo->query("
        SELECT COALESCE(SUM(total),0)
        FROM orders
        WHERE LOWER(payment_status)='paid'
    ")->fetchColumn();

    // Active Products: المنتجات المتاحة بالمخزون
    $active_products = (int)$pdo->query("
        SELECT COUNT(*) FROM products WHERE stock IS NULL OR stock > 0
    ")->fetchColumn();

    // Customers: عدد المستخدمين
    $customers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Recent Orders (آخر 6)
    // ملاحظة: لو ما عندك user_id بالطلبات، استخدمي customer_name/email بدل join
    $recent_orders_stmt = $pdo->query("
        SELECT 
            o.id,
            o.public_id,
            o.order_date,
            o.total,
            o.payment_status,
            o.order_status,
            COALESCE(o.customer_name, CONCAT(u.first_name,' ',u.last_name)) AS customer_name
        FROM orders o
        LEFT JOIN users u ON u.email = o.customer_email
        ORDER BY o.order_date DESC
        LIMIT 6
    ");
    $recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Selling Products (الأكثر مبيعاً)
    $top_products_stmt = $pdo->query("
        SELECT 
            p.id,
            p.name,
            p.price,
            p.image_main_url,
            SUM(oi.quantity) AS sold
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        GROUP BY p.id, p.name, p.price, p.image_main_url
        ORDER BY sold DESC
        LIMIT 5
    ");
    $top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'stats' => [
            'total_orders'   => $total_orders,
            'revenue'        => $revenue,
            'active_products'=> $active_products,
            'customers'      => $customers,
            'recent_orders'  => $recent_orders,
            'top_products'   => $top_products
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'server_error','detail'=>$e->getMessage()]);
}
