<?php
/*************************************************
 * admin-subscribers.php — Subscribers page with
 * same header/sidebar & title style as products
 * Table: subscribers(id,email,subscribed_at)
 *************************************************/
declare(strict_types=1);

/* === Session + Auth + DB === */
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// يستخدم نفس بووتستراب الإدمن/قاعدة البيانات عندك
require_once __DIR__ . '/../backend/auth_db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); echo "DB connection error"; exit; }
$me = require_admin_page_db($pdo, '/matchymatchy/sign-in.php');
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* === Helpers === */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function valid_email(string $e): bool { return (bool)filter_var($e, FILTER_VALIDATE_EMAIL); }

/* === Handle POST: open Gmail compose in new tab === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'gmail_compose') {
    $subject  = trim($_POST['subject'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $mode     = ($_POST['mode'] ?? 'all') === 'selected' ? 'selected' : 'all';
    $selected = isset($_POST['selected']) && is_array($_POST['selected']) ? $_POST['selected'] : [];
    if ($subject === '' || $body === '') { http_response_code(400); echo "Subject and body are required."; exit; }

    $emails = [];
    if ($mode === 'selected' && $selected) {
        foreach ($selected as $e) if (valid_email($e)) $emails[] = $e;
    } else {
        $stmt = $pdo->query("SELECT email FROM subscribers ORDER BY subscribed_at DESC");
        foreach ($stmt as $r) if (!empty($r['email']) && valid_email($r['email'])) $emails[] = $r['email'];
    }
    $emails = array_values(array_unique($emails));
    $bcc = implode(',', $emails);

    // لو بدك To ثابت، حطيه هنا (وإلا خليه فاضي)
    $DEFAULT_TO = '';

    $params = [
        'view' => 'cm',
        'fs'   => '1',
        'to'   => $DEFAULT_TO,
        'bcc'  => $bcc,
        'su'   => $subject,
        'body' => $body,
    ];
    $query = http_build_query($params, arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);
    $url   = "https://mail.google.com/mail/?{$query}";
    header("Location: $url"); exit;
}

/* === Load subscribers === */
$rows = $pdo->query("SELECT id,email,subscribed_at FROM subscribers ORDER BY subscribed_at DESC")->fetchAll();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Admin • Subscribers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- نفس ملفات الإدمن المستخدمة في products -->
    <link rel="stylesheet" href="../styles/sidebar.css" />
    <link rel="stylesheet" href="../styles/topbarAdmin.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            /* نفس المتغيرات المستعملة في products (عدّليها لو عندك ملف ثيم مركزي) */
            --baby:#F9F8F3; --jet:#383838; --teal:#008080; --pink:#E4CFC3; --umber:#715E51; --line:#eee;
        }
        body{ font-family:'Poppins',system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:var(--baby); color:var(--jet); margin:0; }
        .layout{ display:grid; grid-template-columns:260px 1fr; min-height:100vh; }
        .main{ padding:20px; }

        .page-header{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin:8px 0 18px; }
        .page-title{
            margin:0; font-weight:700; line-height:1.2;
            font-size: clamp(20px, 3vw, 28px);
            letter-spacing:.2px;
            background: linear-gradient(90deg, var(--teal), var(--umber));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .page-subtitle{ margin:2px 0 0; color:#666; font-size:14px; }

        .card{ background:#fffdf9; border:1px solid #f0e4db; border-radius:16px; padding:16px; }
        .toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:12px; }
        .btn{ padding:10px 14px; border:0; border-radius:10px; cursor:pointer; font-weight:600; }
        .btn-primary{ background:var(--teal); color:#fff; }
        .btn-ghost{ background:#F2ECE6; color:#333; }
        .input, .textarea, select{ padding:10px 12px; border:1.5px solid var(--pink); border-radius:10px; background:#FCFAF6; }
        .textarea{ min-height:160px; }
        table{ width:100%; border-collapse:separate; border-spacing:0; }
        th,td{ padding:10px 12px; border-bottom:1px solid var(--line); text-align:left; }
        tr:hover td{ background:#fff8f2; }
        .note{ font-size:13px; color:#666; }
        .grid-2{ display:grid; grid-template-columns: 1fr 360px; gap:16px; }
        @media (max-width: 980px){ .layout{ grid-template-columns:1fr; } .grid-2{ grid-template-columns:1fr; } }
        /* Page header */
        .page-head{ display:flex;align-items:center;justify-content:space-between;margin:8px 0 16px;  gap:20px}
        .title-wrap h1{margin:0;font-size:1.7rem;color:var(--umber)}
        .title-wrap p{margin:8px 0 0;color:var(--muted);font-size:.95rem}
    </style>
</head>
<body>
<div class="layout">
    <!-- Sidebar -->
    <div id="sidebar-container"></div>

    <main class="main">
        <!-- Topbar -->
        <div data-include="topBarAdmin.html"></div>

        <!-- ===== نفس الهيدر/العنوان المستخدم في products ===== -->
        <header class="page-head">
            <div class="title-wrap">
                <h1> Subscribers </h1>
                <p>View subscribers and compose to Gmail.</p>
            </div>
        </header>

        <div class="grid-2">
            <div class="card">
                <div class="toolbar">
                    <button class="btn btn-ghost" type="button" id="selectAll">Select All</button>
                    <button class="btn btn-ghost" type="button" id="clearAll">Clear</button>
                    <span class="note">Tip: you can open Gmail for all without selecting anyone (BCC).</span>
                </div>

                <div style="overflow:auto; border:1px solid #f0e4db; border-radius:12px;">
                    <table>
                        <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Email</th>
                            <th>Subscribed</th>
                            <th>Quick</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows): foreach ($rows as $r): ?>
                            <tr>
                                <td><input type="checkbox" class="rowCheck" value="<?= (int)$r['id'] ?>" data-email="<?= h($r['email']) ?>"></td>
                                <td><?= h($r['email']) ?></td>
                                <td><?= h($r['subscribed_at']) ?></td>
                                <td>
                                    <?php
                                    $mailto = 'mailto:'.rawurlencode($r['email'])
                                        .'?subject='.rawurlencode('Matchy Matchy Newsletter')
                                        .'&body='.rawurlencode("Hello!");
                                    ?>
                                    <a href="<?= $mailto ?>">mailto</a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" style="text-align:center;color:#777;">No subscribers yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Composer: فتح Gmail -->
            <div class="card">
                <h3 style="margin-top:0;">Compose in Gmail</h3>
                <form action="" method="post" target="_blank" onsubmit="return injectSelected(this);">
                    <input type="hidden" name="action" value="gmail_compose">
                    <div style="display:grid;gap:8px;">
                        <input class="input" type="text" name="subject" placeholder="Subject" required>
                        <textarea class="textarea" name="body" placeholder="Write your message..." required></textarea>

                        <fieldset style="display:flex;gap:10px;align-items:center;border:0;padding:0;margin:0;">
                            <label><input type="radio" name="mode" value="all" checked> Send to All</label>
                            <label><input type="radio" name="mode" value="selected"> Send to Selected</label>
                        </fieldset>

                        <div id="selectedEmailsContainer"></div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa-regular fa-paper-plane"></i> Open Gmail Compose
                            </button>
                        </div>

                        <div class="note">
                            Opens a new tab with Gmail pre-filled (BCC/Subject/Body). For huge lists, send in batches to avoid long URLs.
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    fetch('sidebar.html').then(r=>r.text()).then(html=>{ try{ document.getElementById('sidebar-container').innerHTML = html; }catch(_){ }});
    document.querySelectorAll('[data-include]').forEach(el=>{
        fetch(el.getAttribute('data-include')).then(r=>r.text()).then(h=>{ el.outerHTML = h; });
    });

    // تحديد/إلغاء
    document.getElementById('selectAll')?.addEventListener('click', ()=>{
        document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = true);
    });
    document.getElementById('clearAll')?.addEventListener('click', ()=>{
        document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = false);
    });

    // حقن selected[] عند اختيار Send to Selected
    function injectSelected(form){
        const mode = form.querySelector('input[name="mode"]:checked')?.value || 'all';
        const container = document.getElementById('selectedEmailsContainer');
        container.innerHTML = '';
        if (mode === 'selected') {
            const emails = Array.from(document.querySelectorAll('.rowCheck:checked'))
                .map(cb => cb.dataset.email)
                .filter(Boolean);
            emails.forEach(e=>{
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected[]';
                input.value = e;
                container.appendChild(input);
            });
            if (!emails.length) return confirm('No recipients selected. Continue with empty BCC?');
        }
        return true;
    }
</script>
<script src="../js/adminbar.js" defer></script>
<script src="../js/includeadminBar.js" defer></script>
</body>
</html>
