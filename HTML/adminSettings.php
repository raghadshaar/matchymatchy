<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/auth_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'pdo_init_failed']);
    exit;
}
$me = require_admin_page_db($pdo, '/matchymatchy/sign-in.php');

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Admin • Settings — Matchy Matchy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <!-- Fonts + Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- نفس ستايلات الأدمن -->
    <link rel="stylesheet" href="../styles/admin.css"/>
    <link rel="stylesheet" href="../styles/topbarAdmin.css"/>

    <style>
        /* بطاقات ناعمة بنفس روح الأدمن */
        .card {
            background:#fff; border:1px solid rgba(113,94,81,.12);
            border-radius:16px; padding:20px;
            box-shadow:0 10px 25px rgba(113,94,81,.08);
        }
        .form-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
        .form-grid .full { grid-column: 1 / -1; }
        .input { width:100%; padding:10px 12px; border:2px solid #E4CFC3; border-radius:10px; outline:none; }
        .input:focus { border-color:#008080; }
        .btn { background:#008080; color:#fff; border:0; border-radius:10px; padding:12px 16px; cursor:pointer; font-weight:600; }
        .btn:disabled { opacity:.6; cursor:not-allowed; }
        .avatar-wrap{ position:relative; display:inline-block; }
        .avatar-wrap img{ width:96px; height:96px; border-radius:50%; object-fit:cover; border:2px solid #eee; }
        .avatar-wrap label{
            position:absolute; right:-4px; bottom:-4px; background:#fff; border:1px solid #ddd;
            width:36px; height:36px; display:grid; place-items:center; border-radius:50%; cursor:pointer;
        }
        .hint{ color:#6b7280; font-size:.9rem; }
        .title { font-weight:600; font-size:1.25rem; margin:0 0 10px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div id="sidebar-container"></div>

<!-- MAIN -->
<main class="main">
    <!-- الهيدر (Top Bar) -->
    <div data-include="topBarAdmin.html"></div>

    <!-- المحتوى -->
    <section class="content">
        <h1 class="page-title">Admin Settings</h1>
        <p class="page-info">Update your profile information and password.</p>

        <div class="card" style="max-width:920px;">
            <!-- Avatar -->
            <div style="display:flex; align-items:center; gap:16px; margin-bottom:18px;">
                <div class="avatar-wrap">
                    <img id="avatar" src="../images/placeholder.jpg" alt="Avatar">
                    <label for="avatar_file" title="Change photo"><i class="fa-solid fa-camera"></i></label>
                    <input type="file" id="avatar_file" accept="image/*" hidden>
                </div>
                <div class="hint">
                    <div><strong>Profile Photo</strong></div>
                    <div>Allowed: JPG/PNG/WebP, Max 2MB.</div>
                </div>
            </div>

            <!-- Form -->
            <form id="settings-form" class="form-grid" enctype="multipart/form-data">
                <div>
                    <label class="hint">First Name</label>
                    <input class="input" id="first_name" name="first_name" required>
                </div>
                <div>
                    <label class="hint">Last Name</label>
                    <input class="input" id="last_name" name="last_name" required>
                </div>
                <div class="full">
                    <label class="hint">Email (read-only)</label>
                    <input class="input" id="email" name="email" readonly>
                </div>
                <div class="full">
                    <label class="hint">Avatar URL (optional)</label>
                    <input class="input" id="avatar_url" name="avatar" placeholder="uploads/....jpg">
                </div>
                <div class="full">
                    <label class="hint">New Password (optional)</label>
                    <input type="password" class="input" id="password" name="password" placeholder="••••••••">
                </div>
                <div class="full" style="display:flex; gap:12px; align-items:center;">
                    <button type="submit" class="btn" id="saveBtn">Save Changes</button>
                    <span id="response" class="hint"></span>
                </div>
            </form>
        </div>
    </section>
</main>

<!-- Scripts (نفس سكربتات تضمين السايدبار والهيدر) -->
<script src="../js/admin.js"></script>
<script src="../js/adminbar.js" defer></script>
<script src="../js/includeadminBar.js" defer></script>

<script>
    // === تحميل بيانات الأدمن (Session-based) ===
    async function loadAdminData(){
        try{
            const res = await fetch('../backend/settings.php', { credentials:'include' });
            if(!res.ok) throw new Error('Failed to fetch');
            const u = await res.json();

            if ((u && u.ok === false) || u.error){
                document.getElementById('response').textContent = u.error || 'Unauthorized';
                return;
            }
            document.getElementById('first_name').value = u.first_name || '';
            document.getElementById('last_name').value  = u.last_name  || '';
            document.getElementById('email').value      = u.email      || '';
            document.getElementById('avatar_url').value = u.avatar     || '';

            const avatarSrc = u.avatar ? `../backend/${u.avatar}` : '../images/placeholder.jpg';
            document.getElementById('avatar').src = avatarSrc;
        }catch(err){
            console.error(err);
            document.getElementById('response').textContent = 'Error loading data';
        }
    }

    // Preview + validate avatar file
    document.getElementById('avatar_file').addEventListener('change', (e)=>{
        const file = e.target.files[0];
        if(!file) return;

        const allowed = ['image/jpeg','image/png','image/webp'];
        if (!allowed.includes(file.type)){
            document.getElementById('response').textContent = 'Only JPG, PNG, WebP allowed';
            e.target.value = ''; return;
        }
        if (file.size > 2*1024*1024){
            document.getElementById('response').textContent = 'Image must be less than 2MB';
            e.target.value = ''; return;
        }
        document.getElementById('avatar').src = URL.createObjectURL(file);
        document.getElementById('response').textContent = '';
    });

    // حفظ الإعدادات
    document.getElementById('settings-form').addEventListener('submit', async (e)=>{
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        const fd  = new FormData(e.target);
        const f   = document.getElementById('avatar_file').files[0];
        if (f) fd.append('avatar_file', f);

        btn.disabled = true; const prev = btn.textContent; btn.textContent = 'Saving...';
        try{
            const res = await fetch('../backend/settings.php', {
                method:'POST', body:fd, credentials:'include'
            });
            const j = await res.json();
            document.getElementById('response').textContent = j.message || j.error || 'Saved.';
            if (j.avatar){
                document.getElementById('avatar').src = `../backend/${j.avatar}`;
                document.getElementById('avatar_url').value = j.avatar;
            }
        }catch(err){
            console.error(err);
            document.getElementById('response').textContent = 'Error saving settings';
        }finally{
            btn.disabled = false; btn.textContent = prev;
        }
    });

    loadAdminData();
</script>
</body>
</html>
