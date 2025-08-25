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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Matchy Matchy — Admin • Categories</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Shared styles -->
    <link rel="stylesheet" href="../styles/sidebar.css" />
    <link rel="stylesheet" href="../styles/products.css" />
    <link rel="stylesheet" href="../styles/topbarAdmin.css" />

    <style>
        :root { --drop-dash: 4px dashed var(--border); }

        .cat-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; }
        .cat-card{ background:var(--baby); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; display:flex; flex-direction:column; }
        .cat-media{ height:180px; background:#f6f5f2; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:center; }
        .cat-media img{ width:100%; height:100%; object-fit:cover; }
        .cat-body{ padding:14px; display:flex; flex-direction:column; gap:8px; flex:1; }
        .cat-title{ margin:0; font-weight:600; color:var(--jet); }
        .cat-desc{ margin:0; color:var(--muted); font-size:.92rem; min-height:38px; }
        .cat-foot{ display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-top:1px solid var(--border) }
        .cat-actions{ display:flex; gap:8px }
        .stat{ color:var(--umber); font-weight:500 }

        .badge.st-active{ background:#e9f8f1; border:1px solid #cfeadf; color:#1b8e57; padding:6px 10px; border-radius:999px; font-weight:600; font-size:.82rem }
        .badge.st-hidden{ background:#f2f2f2; border:1px solid #e5e5e5; color:#6b7280; padding:6px 10px; border-radius:999px; font-weight:600; font-size:.82rem }

        /* Drawer upload dropzone */
        .dropzone{ border:var(--drop-dash); border-radius:14px; padding:18px; display:grid; place-items:center; text-align:center; color:var(--muted); background:var(--baby) }
        .dropzone.drag{ background:#f4efe9; }
        .dropzone .hint{ font-size:.9rem }
        .dz-preview{ margin-top:10px; display:none }
        .dz-preview img{ width:100%; max-height:160px; object-fit:contain; border:1px solid var(--border); border-radius:10px; }

        .toolbar .field{ min-width:240px }
        .toolbar .btn i{ margin-right:6px }

        .icon-link{ width:38px; height:38px; border-radius:10px }






        /* خلي زر Clear بنفس ارتفاع الفيلدز (44px) */
        #clearFilters {
            height: 44px;         /* نفس ارتفاع الحقول */
            padding: 10px 12px;   /* نفس الحشوة */
            border-radius: 12px;  /* نفس انحناء الزوايا */
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }



        .cat-note{
            background:#fff;
            border-top:1px solid var(--border);
            color:var(--umber);
            text-align:center;
            font-size:.85rem;
            padding:6px 10px;
        }













        /* ===== Toolbar band (مطابق لستايل الـ Reviews) ===== */
        :root{
            --ctl-h: 44px;           /* ارتفاع موحّد لكل العناصر */
            --ctl-br: 12px;          /* نصف القطر */
            --ctl-pad: 10px 12px;    /* حشوة داخليّة */
        }

        /* الحاوية الخلفية مع التدرّج */
        .toolbar.band.cat-toolbar{
            background: linear-gradient(90deg, #e9d9d1 0%, #00808022 40%, #00808055 100%); /* من ألوان البالِت */
            border: 1px solid #e8e6e2;
            border-radius: 16px;
            padding: 12px;
            box-shadow: var(--shadow);
        }

        /* صف العناصر */
        .tb-row{
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* الفيلد العام */
        .toolbar.band .field{
            display:flex; align-items:center; gap:10px;
            background:#fff; border:1px solid var(--border);
            height: var(--ctl-h);
            padding: 0 12px;
            border-radius: var(--ctl-br);
            min-width: 240px;
            box-shadow: var(--shadow);
        }
        .toolbar.band .field i{ color:var(--umber); }
        .toolbar.band .field input,
        .toolbar.band .field select{
            border:0; outline:0; background:transparent;
            height: calc(var(--ctl-h) - 2px);
            line-height: calc(var(--ctl-h) - 2px);
            font: inherit;
        }

        /* سيرتش بعرض مرن */
        .field-search{ flex:1; }

        /* الأزرار بنفس الارتفاع */
        .toolbar.band .btn{
            height: var(--ctl-h);
            padding: var(--ctl-pad);
            border-radius: var(--ctl-br);
            display:inline-flex; align-items:center; gap:8px;
            box-shadow: var(--shadow);
        }
        .toolbar.band .btn i{ font-size: 14px; }

        /* زر Clear ghost بنفس ارتفاع الباقي */
        .toolbar.band .btn-clear{
            background:#fff;
            border:1px solid var(--border);
            color:var(--jet);
        }

        /* مسافة قبل زر الإضافة */
        .fx-spacer{ flex:1; }

        /* استجابة */
        @media (max-width: 900px){
            .tb-row{ flex-wrap: wrap; }
            .toolbar.band .field{ min-width: min(100%, 320px); flex:1; }
            .fx-spacer{ display:none; }
            .toolbar.band .btn-clear{ order: 3; }
            #addCategoryBtn{ width: 100%; justify-content:center; }
        }

    </style>
</head>
<body>
<div class="layout">
    <!-- Sidebar -->
    <div id="sidebar-container"></div>

    <!-- Main -->
    <main class="main">
        <!-- Topbar -->
        <div data-include="topBarAdmin.html"></div>

        <!-- Header -->
        <div class="page-head">
            <div class="title-wrap">
                <h1>Categories Management</h1>
                <p class="muted">Create and organize storefront categories. Used for navigation and product filtering.</p>
            </div>
        </div>

        <div class="toolbar">
            <div class="field" style="flex:1">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="searchInput" type="search" placeholder="Search categories…">
            </div>
            <div class="field">
                <i class="fa-solid fa-signal"></i>
                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option>Active</option>
                    <option>Hidden</option>
                </select>
            </div>
            <div class="field">
                <i class="fa-solid fa-diagram-project"></i>
                <select id="typeFilter" aria-label="Filter by kind">
                    <option value="">All Categories</option>
                    <option value="main">Main Categories (Parent)</option>
                    <option value="child">Child Categories</option>
                </select>
            </div>

            <button class="btn btn-ghost" id="clearFilters"><i class="fa-solid fa-rotate-left"></i> Clear</button>
            <div style="flex:1"></div>
            <button class="btn btn-teal" id="addCategoryBtn"><i class="fa-solid fa-plus"></i> Add Category</button>
        </div>

        <!-- Grid -->
        <section class="card">
            <div id="grid" class="cat-grid"></div>

            <!-- Empty State -->
            <div id="emptyState" class="empty" style="display:none">
                <div class="emoji">🧩</div>
                <h3 style="margin:0 0 6px;color:var(--umber)">No categories yet</h3>
                <p style="margin:0 0 14px">Click “Add Category” to create your first category.</p>
                <button class="btn btn-teal" id="emptyAddBtn"><i class="fa-solid fa-plus"></i> Add Category</button>
            </div>

            <!-- Pagination -->
            <div class="pager">
                <div class="pager-info" id="pagerInfo"></div>
                <button class="page-btn" id="prevPage"><i class="fa-solid fa-chevron-left"></i></button>
                <div id="pageNums" style="display:flex;gap:6px"></div>
                <button class="page-btn" id="nextPage"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>
    </main>
</div>

<!-- Drawer -->
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<div class="drawer" id="drawer">
    <h3 id="drawerTitle">Add New Category</h3>
    <div class="form-grid">
        <div class="form-item" style="grid-column:1/-1">
            <label>Category Image</label>
            <div id="dropzone" class="dropzone">
                <div>
                    <div style="font-size:28px;margin-bottom:6px"><i class="fa-regular fa-image"></i></div>
                    <div class="hint">Click to upload or drag and drop<br><small>PNG, JPG up to 2MB</small></div>
                    <input id="fileInput" type="file" accept="image/*" style="display:none"/>
                    <div class="dz-preview" id="dzPreview"><img id="dzImg" alt="Preview"></div>
                </div>
            </div>
            <input id="imageUrl" class="input" placeholder="…or paste an image URL (optional)" style="margin-top:8px"/>
        </div>

        <div class="form-item">
            <label>Category Name</label>
            <input class="input" id="cName" placeholder="e.g., Baby"/>
        </div>
        <div class="form-item">
            <label>Parent Category</label>
            <select class="select" id="cParent"></select>
        </div>

        <div class="form-item" style="grid-column:1/-1">
            <label>Description</label>
            <textarea class="textarea" id="cDesc" placeholder="Brief description of the category"></textarea>
        </div>

        <div class="form-item">
            <label>Status</label>
            <select class="select" id="cStatus"><option>Active</option><option>Hidden</option></select>
        </div>
        <div class="form-item">
            <label>Products Count</label>
            <input class="input" id="cCount" type="number" min="0" placeholder="e.g., 156"/>
        </div>
    </div>
    <div class="drawer-actions">
        <button class="btn btn-ghost" id="cancelDrawer">Cancel</button>
        <button class="btn btn-teal" id="saveCategory">Save</button>
    </div>
</div>
<script>
    const PAGE_SIZE = 6;
    const $  = (s,c=document)=>c.querySelector(s);
    const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));

    /* Sidebar include + active link */
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const html = await fetch('sidebar.html').then(r=>r.text());
            const wrap = $('#sidebar-container');
            wrap.innerHTML = html;
            $$('a', wrap).forEach(a=>{
                if((a.textContent||'').trim().toLowerCase()==='categories') a.classList.add('active');
            });
        } catch {}
        await loadParents();   // حمّل خيارات الـParent
        await loadCategories();// حمّل الداتا
    });

    /* ====== State ====== */
    let state = { q:'', status:'', kind:'', page:1, total:0, pages:1, rows:[] };
    let editingId = null;
    let imageBlobUrl = '';

    /* ====== Load parents for <select> ====== */
    async function loadParents(){
        try{
            const res = await fetch('../backend/categories_api.php?parents=1');
            const j   = await res.json();
            const opts = (j.parents||[]).sort((a,b)=>a.name.localeCompare(b.name));
            $('#cParent').innerHTML =
                '<option value="">None (Top Level)</option>' +
                opts.map(o=>`<option value="${o.id}">${o.name}</option>`).join('');
        }catch(e){ console.warn('loadParents', e); }
    }

    /* ====== Fetch list ====== */
    async function loadCategories(){
        const PAGE_SIZE = 8; // أو استخدمي الموجود عندك
        const params = new URLSearchParams({
            page: state.page,
            limit: PAGE_SIZE,
            search: state.q,
            status: state.status,
            kind: state.kind  // <-- هذا الجديد
        });

        const res = await fetch(`../backend/categories_api.php?${params}`);
        const ct  = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const t = await res.text();
            throw new Error(`Server did not return JSON: ${t.substring(0,120)}...`);
        }
        const j = await res.json();
        if (!j.success && !Array.isArray(j.categories)) {
            throw new Error(j.error || 'Failed to load categories');
        }

        state.rows  = j.categories || [];
        state.total = j.total ?? 0;
        state.pages = j.pages ?? 1;

        render();
    }


    /* ====== Render ====== */
    function statusBadgeClass(s){
        const t = (s||'').toLowerCase();
        return t==='hidden' || t==='hidden' ? 'badge st-hidden' : 'badge st-active';
    }
    function humanStatus(s){ return (s && s[0]) ? (s[0].toUpperCase()+s.slice(1)) : 'Active'; }

    function render(){
        const grid = $('#grid');
        grid.innerHTML = state.rows.map(c => {
            const note = c.parent_id
                ? `Child of ${c.parent_name || '—'}`
                : 'Main category';

            return `
    <div class="cat-card">
      <div class="cat-media">
        ${c.image_url ? `<img src="${c.image_url}" alt="${c.name}">` : '<span class="muted">No image</span>'}
      </div>

      <div class="cat-note">${note}</div>

      <div class="cat-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px">
          <h4 class="cat-title">${c.name}</h4>
          <span class="${statusBadgeClass(c.status)}">${c.statusTitle || humanStatus(c.status||'active')}</span>
        </div>
        <p class="cat-desc">${c.description||''}</p>
      </div>

      <div class="cat-foot">
<div class="stat">
  <i class="fa-solid fa-box"></i> ${c.product_count || 0} products
</div>
        <div class="cat-actions">
          <button class="icon-link" title="Edit" data-edit="${c.id}"><i class="fa-regular fa-pen-to-square"></i></button>
          <button class="icon-link" title="Delete" data-del="${c.id}"><i class="fa-regular fa-trash-can"></i></button>
        </div>
      </div>
    </div>
  `;
        }).join('');

        $('#emptyState').style.display = state.rows.length ? 'none' : 'block';
        $('#pagerInfo').textContent = state.rows.length
            ? `Showing ${Math.min((state.page-1)*PAGE_SIZE+1, state.total)}–${Math.min(state.page*PAGE_SIZE,state.total)} of ${state.total} categories`
            : 'No results';

        const pn = $('#pageNums'); pn.innerHTML='';
        for(let i=1;i<=state.pages;i++){
            const b=document.createElement('button');
            b.className='page-btn' + (i===state.page?' active':'');
            b.textContent=i; b.onclick=()=>{ state.page=i; loadCategories(); };
            pn.appendChild(b);
        }
        $('#prevPage').disabled = state.page<=1;
        $('#nextPage').disabled = state.page>=state.pages;

        $$('[data-edit]').forEach(b=> b.onclick = ()=> openDrawerForEdit(+b.dataset.edit));
        $$('[data-del]').forEach(b=> b.onclick = ()=> deleteCategory(+b.dataset.del));
    }

    /* ====== Filters ====== */
    $('#searchInput').oninput = e => { state.q = e.target.value; state.page=1; loadCategories(); };
    $('#statusFilter').onchange = e => { state.status = e.target.value; state.page=1; loadCategories(); };
    $('#typeFilter').onchange = e => {
        state.kind = e.target.value;   // '' | 'main' | 'child'
        state.page = 1;
        loadCategories();
    };
    $('#clearFilters').onclick = () => {
        state = { q:'', status:'', kind:'', page:1, total:0, pages:1, rows:[] };
        $('#searchInput').value = '';
        $('#statusFilter').value = '';
        $('#typeFilter').value  = '';
        loadCategories();
    };


    $('#prevPage').onclick = ()=>{ if (state.page>1){ state.page--; loadCategories(); } };
    $('#nextPage').onclick = ()=>{ if (state.page<state.pages){ state.page++; loadCategories(); } };

    /* ====== Drawer ====== */
    const drawer   = $('#drawer'), backdrop = $('#drawerBackdrop');
    const dz       = $('#dropzone'), fi = $('#fileInput'), dzPrev = $('#dzPreview'), dzImg = $('#dzImg');
    const els = {
        name: $('#cName'), desc: $('#cDesc'), parent: $('#cParent'),
        status: $('#cStatus'), count: $('#cCount'), imageUrl: $('#imageUrl')
    };

    function resetForm(){
        editingId = null;
        $('#drawerTitle').textContent = 'Add New Category';
        els.name.value=''; els.desc.value=''; els.parent.value='';
        els.status.value='Active'; els.count.value='0';
        els.imageUrl.value=''; imageBlobUrl=''; dzPrev.style.display='none'; dzImg.src='';
    }
    function openDrawer(){ drawer.classList.add('open'); backdrop.classList.add('show'); }
    function closeDrawer(){ drawer.classList.remove('open'); backdrop.classList.remove('show'); resetForm(); }
    $('#cancelDrawer').onclick=closeDrawer; backdrop.onclick=closeDrawer;

    $('#addCategoryBtn').onclick = ()=>{ resetForm(); openDrawer(); };
    $('#emptyAddBtn').onclick    = ()=>{ resetForm(); openDrawer(); };

    /* load single for edit */
    async function openDrawerForEdit(id){
        try{
            const res = await fetch(`../backend/categories_api.php?id=${id}`);
            const ct  = res.headers.get('content-type')||'';
            if (!ct.includes('application/json')) throw new Error('Server did not return JSON');
            const c = await res.json();

            editingId = id;
            $('#drawerTitle').textContent = 'Edit Category';
            els.name.value = c.name || '';
            els.desc.value = c.description || '';
            els.parent.value = c.parent_id || '';
            els.status.value = c.statusTitle || 'Active';
            els.count.value  = c.product_count || 0;
            els.imageUrl.value = c.image_url || '';
            if (c.image_url){ dzPrev.style.display='block'; dzImg.src=c.image_url; imageBlobUrl=c.image_url; }
            openDrawer();
        }catch(e){
            console.error('openDrawerForEdit', e);
            alert('Failed to load category');
        }
    }

    /* save (create/update) */
    function collectPayload(){
        const name = els.name.value.trim();
        if (!name){ alert('Please enter a category name'); return null; }
        const parent_id = parseInt(els.parent.value||'0',10) || 0;
        const status = els.status.value || 'Active';
        const img = imageBlobUrl || els.imageUrl.value.trim();
        return {
            id: editingId || undefined,
            name,
            parent_id,
            description: els.desc.value.trim(),
            status,                 // Active/Hidden (السيرفر يحوّلها lower)
            image_url: img
        };
    }
    async function saveCategory(){
        const payload = collectPayload(); if (!payload) return;
        try{
            const res = await fetch('../backend/categories_api.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const ct = res.headers.get('content-type')||'';
            if (!ct.includes('application/json')) {
                const t = await res.text(); throw new Error(`Bad response: ${t.substring(0,120)}...`);
            }
            const j = await res.json();
            if (!j.success) throw new Error(j.error||'save failed');
            closeDrawer();
            await loadParents();  // يمكن الاسم تغيّر → حدّث القائمة
            await loadCategories();
        }catch(e){
            console.error('saveCategory', e);
            alert('Failed to save category: ' + e.message);
        }
    }
    $('#saveCategory').onclick = saveCategory;

    /* delete */
    async function deleteCategory(id){
        if (!confirm('Delete this category?')) return;
        try{
            const res = await fetch(`../backend/categories_api.php?id=${id}`, { method:'DELETE' });
            const j   = await res.json();
            if (!j.success) throw new Error(j.error||'delete failed');
            await loadParents();
            await loadCategories();
        }catch(e){
            alert('Failed to delete: ' + e.message);
        }
    }

    /* ====== Dropzone upload (re-use backend/upload_image.php) ====== */
    function setPreview(src){ if(src){ dzImg.src=src; dzPrev.style.display='block'; imageBlobUrl=src; } }
    dz.addEventListener('click', ()=> fi.click());
    fi.addEventListener('change', async e=>{
        const f = e.target.files?.[0]; if (!f) return;
        const fd = new FormData(); fd.append('file', f);
        try{
            const res = await fetch('../backend/upload_image.php', {method:'POST', body:fd});
            const j = await res.json();
            if (!j.ok) throw new Error(j.error||'upload failed');
            setPreview(j.url);
        }catch(err){ alert(err.message); }
    });
    ['dragenter','dragover'].forEach(ev=> dz.addEventListener(ev, e=>{ e.preventDefault(); dz.classList.add('drag'); }));
    ['dragleave','drop'].forEach(ev=> dz.addEventListener(ev, e=>{ e.preventDefault(); dz.classList.remove('drag'); }));
    dz.addEventListener('drop', async e=>{
        const f = e.dataTransfer.files?.[0]; if (!f) return;
        const fd = new FormData(); fd.append('file', f);
        try{
            const res = await fetch('../backend/upload_image.php', {method:'POST', body:fd});
            const j = await res.json();
            if (!j.ok) throw new Error(j.error||'upload failed');
            setPreview(j.url);
        }catch(err){ alert(err.message); }
    });
    $('#imageUrl').addEventListener('change', e=>{ const url=e.target.value.trim(); if(url) setPreview(url); });
</script>


<script src="../js/adminbar.js" defer></script>
<script src="../js/includeadminBar.js" defer></script>
</body>
</html>
