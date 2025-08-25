<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/auth_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'pdo_init_failed']);
    exit;
}
$me = require_admin_page_db($pdo, '/matchymatchy/sign-in.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Matchy Matchy — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Fonts + Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Styles -->
    <link rel="stylesheet" href="../styles/topbarAdmin.css" />
    <link rel="stylesheet" href="../styles/admin.css" />

</head>
<body>
<!-- SIDEBAR -->
<div id="sidebar-container"></div>

<!-- MAIN -->
<main class="main">
    <div data-include="topBarAdmin.html"></div>

    <!-- CONTENT -->
    <section class="content">
        <h1 class="page-title">Dashboard Overview</h1>
        <p class="page-info">Welcome back! Here's what's happening with your store today.</p>

        <!-- STATS -->
        <div class="stats" id="stats">
            <div class="stat-card">
                <div class="stat-head">
                    <span>Total Orders</span>
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <div class="stat-val" id="stat-orders">Loading…</div>
                <div class="stat-sub" id="stat-orders-sub">&nbsp;</div>
            </div>

            <div class="stat-card">
                <div class="stat-head">
                    <span>Revenue</span>
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="stat-val" id="stat-revenue">Loading…</div>
                <div class="stat-sub" id="stat-revenue-sub">&nbsp;</div>
            </div>

            <div class="stat-card">
                <div class="stat-head">
                    <span>Active Products</span>
                    <i class="fa-solid fa-circle-dot"></i>
                </div>
                <div class="stat-val" id="stat-products">Loading…</div>
                <div class="stat-sub" id="stat-products-sub">&nbsp;</div>
            </div>

            <div class="stat-card">
                <div class="stat-head">
                    <span>Customers</span>
                    <i class="fa-regular fa-user"></i>
                </div>
                <div class="stat-val" id="stat-customers">Loading…</div>
                <div class="stat-sub" id="stat-customers-sub">&nbsp;</div>
            </div>
        </div>

        <!-- GRID -->
        <div class="panels">
            <!-- Recent Orders -->
            <div class="panel">
                <div class="panel-head">
                    <h2>Recent Orders</h2>
                    <a href="orders.php" class="view-all">View All</a>
                </div>

                <div class="table" id="recent-orders">
                    <div class="t-head">
                        <span>#</span><span>Customer</span><span>Status</span><span>Total</span>
                    </div>
                    <div class="t-row">
                        <span class="col-span-full" style="grid-column: 1 / -1; color:#6b7280;">Loading…</span>
                    </div>
                </div>
            </div>

            <!-- Top Selling -->
            <div class="panel">
                <div class="panel-head">
                    <h2>Top Selling Products</h2>
                    <a href="products.php" class="view-all">View All</a>
                </div>

                <ul class="product-list" id="top-products">
                    <li class="product-item" data-loading="true">
                        <div class="info"><strong style="color:#6b7280;">Loading…</strong></div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</main>

<script>
    function nis(n){ return '₪' + Number(n||0).toFixed(2); }
    function badge(status){
        const s = String(status||'').toLowerCase();
        let cls = 'badge-amber', txt = status || '—';
        if (s === 'paid' || s === 'shipped') cls = 'badge-teal';
        else if (s === 'delivered') cls = 'badge-pink';
        return `<span class="badge ${cls}">${txt}</span>`;
    }

    async function loadDashboard(){
        const statOrders   = document.getElementById('stat-orders');
        const statRevenue  = document.getElementById('stat-revenue');
        const statProducts = document.getElementById('stat-products');
        const statCusts    = document.getElementById('stat-customers');

        const ordersWrap   = document.getElementById('recent-orders');
        const topList      = document.getElementById('top-products');

        try{
            const res = await fetch('../backend/admin_stats.php', {headers:{'Accept':'application/json'}});
            const j = await res.json();
            if (!j.ok) throw new Error(j.error || 'Failed');

            const s = j.stats || {};

            // ========== Stats ==========
            statOrders.textContent   = s.total_orders ?? 0;
            statRevenue.textContent  = nis(s.revenue);
            statProducts.textContent = s.active_products ?? 0;
            statCusts.textContent    = s.customers ?? 0;

            // (اختياري) سطر فرعي
            document.getElementById('stat-orders-sub').textContent   = '';
            document.getElementById('stat-revenue-sub').textContent  = '';
            document.getElementById('stat-products-sub').textContent = '';
            document.getElementById('stat-customers-sub').textContent= '';

            // ========== Recent Orders ==========
            const head = `<div class="t-head"><span>#</span><span>Customer</span><span>Status</span><span>Total</span></div>`;
            const rows = (s.recent_orders||[]).map(o => `
        <div class="t-row">
          <span>#${o.public_id || o.id}</span>
          <span>${o.customer_name || '—'}</span>
          <span>${badge(o.payment_status || o.order_status)}</span>
          <span>${nis(o.total)}</span>
        </div>
      `).join('');
            ordersWrap.innerHTML = head + (rows || `<div class="t-row"><span class="col-span-full" style="grid-column:1/-1;color:#9ca3af;">No recent orders</span></div>`);

            // ========== Top Products ==========
            const items = (s.top_products||[]).map(p => `
        <li class="product-item">
          <img src="${p.image_main_url || '../images/placeholder.jpg'}" alt="">
          <div class="info">
            <strong>${p.name}</strong>
            <small>Top Selling</small>
            <div class="meta"><span class="price">${nis(p.price)}</span> <span class="dot">•</span> <span>${p.sold||0} sold</span></div>
          </div>
        </li>
      `).join('');
            topList.innerHTML = items || `<li class="product-item"><div class="info"><strong style="color:#9ca3af;">No data</strong></div></li>`;

        }catch(err){
            console.error(err);
            statOrders.textContent = statRevenue.textContent = statProducts.textContent = statCusts.textContent = '—';
            ordersWrap.innerHTML = `
        <div class="t-head"><span>#</span><span>Customer</span><span>Status</span><span>Total</span></div>
        <div class="t-row"><span class="col-span-full" style="grid-column:1/-1;color:#ef4444;">Error loading data</span></div>
      `;
            topList.innerHTML = `<li class="product-item"><div class="info"><strong style="color:#ef4444;">Error loading data</strong></div></li>`;
        }
    }
    loadDashboard();
</script>

<script src="../js/admin.js"></script>
<script src="../js/adminbar.js" defer></script>
<script src="../js/includeadminBar.js" defer></script>
</body>
</html>
