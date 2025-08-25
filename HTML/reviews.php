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
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Matchy Matchy — Admin • Reviews</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Project base styles (يحافظ على الشكل القديم) -->
    <link rel="stylesheet" href="../styles/sidebar.css"/>
    <link rel="stylesheet" href="../styles/products.css"/>
    <link rel="stylesheet" href="../styles/topbarAdmin.css"/>
    <link rel="stylesheet" href="../styles/reviews.css"/>

    <!-- إضافات خفيفة فقط -->
    <style>
        .layout{min-height:100vh;display:grid;grid-template-columns:260px 1fr}
        #sidebar-container{background:#fff;border-right:1px solid #eee}

        /* الكارد */
        .review-item{ position:relative; padding-top:6px; }
        .review-item .review-head{ display:flex; align-items:flex-start; gap:12px; }
        .review-item .review-head .left{ display:flex; gap:12px; align-items:flex-start; flex:1; min-width:0; }
        /* شارة الحالة مثبتة يمين */
        .review-item .badge-pin{
            position:absolute; top:12px; right:16px; z-index:2; white-space:nowrap;
        }
        /* تعطيل أي عمود يمين قديم */
        .review-item .review-head .right{ display:none !important; }

        /* Avatar بحرف أول */
        .mm-avatar{width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;overflow:hidden}
        .mm-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}

        /* نجوم */
        .stars{color:#f59e0b;font-size:14px}

        /* شارات الحالة */
        .badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.78rem;font-weight:600}
        .badge--visible{background:#DCFCE7;color:#166534}
        .badge--hidden{background:#F3F4F6;color:#111827}
        .badge--flagged{background:#FEF3C7;color:#92400E}

        /* سبب التبليغ (يظهر فقط في تبويب Flagged) */
        .flag-reason{margin-top:8px;padding:10px 12px;border-radius:12px;background:#FFF7ED;color:#92400E;font-size:.92rem;display:none}
        .flag-reason.on{display:inline-flex;gap:8px;align-items:flex-start}

        /* صفحات */
        .page-btn{padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px;background:#fff}
        .page-btn.active{background:#0f766e;color:#fff;border-color:#0f766e}

        @media (max-width:640px){
            .review-item .badge-pin{ top:10px; right:10px; transform:scale(.95); }
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
                <h1>Reviews</h1>
                <p class="muted">Manage and moderate product feedback across Baby, Kids, and Family Matching collections.</p>
            </div>
            <button class="btn btn-ghost" id="showAnalytics">
                <i class="fa-solid fa-chart-line"></i> Show Analytics
            </button>
        </div>

        <!-- Toolbar (نفس شكل القديم مع mm-select) -->
        <div class="toolbar">
            <div class="top-row">
                <div class="field field-search" style="flex:1">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="q" type="search" placeholder="Search products, review">
                </div>

                <div class="field field-cat">
                    <i class="fa-solid fa-layer-group"></i>
                    <!-- Parent categories من قاعدة البيانات -->
                    <select id="cat"></select>
                </div>

                <div class="field ratings-field">
                    <i class="fa-solid fa-star"></i>
                    <select id="rating">
                        <option value="">All Ratings</option>
                        <option value="5">★★★★★</option>
                        <option value="4">★★★★☆</option>
                        <option value="3">★★★☆☆</option>
                        <option value="2">★★☆☆☆</option>
                        <option value="1">★☆☆☆☆</option>
                    </select>
                </div>

                <div class="field field-sort">
                    <i class="fa-solid fa-arrow-down-short-wide"></i>
                    <select id="sort">
                        <option value="new">Newest First</option>
                        <option value="old">Oldest First</option>
                        <option value="high">Highest Rated</option>
                        <option value="low">Lowest Rated</option>
                    </select>
                </div>

                <button class="btn btn-ghost top-clear-btn" id="clearFilters">
                    <i class="fa-solid fa-rotate-left"></i> Clear Filters
                </button>
            </div>

            <!-- تبويبات الحالة -->
            <div class="review-tabs" id="statusTabs">
                <button class="pill active" data-mode="all">All</button>
                <button class="pill" data-mode="visible">Visible</button>
                <button class="pill" data-mode="hidden">Hidden</button>
                <button class="pill" data-mode="flagged">Flagged</button>
            </div>
        </div>

        <!-- Error -->
        <div id="alert" class="alert error" style="display:none"></div>

        <!-- List -->
        <section class="card" id="listWrap">
            <div id="reviewsList" class="reviews-list"></div>

            <div id="emptyState" class="empty" style="display:none">
                <div class="emoji">💬</div>
                <h3 style="margin:0 0 6px;color:var(--umber)">No reviews found</h3>
                <p class="muted" style="margin:0">Try adjusting filters or search terms.</p>
            </div>

            <div class="pager">
                <div class="pager-info" id="pagerInfo"></div>
                <button class="page-btn" id="prevPage"><i class="fa-solid fa-chevron-left"></i></button>
                <div id="pageNums" style="display:flex;gap:6px"></div>
                <button class="page-btn" id="nextPage"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>
    </main>
</div>

<!-- Helpers -->
<script src="../js/adminbar.js" defer></script>
<script src="../js/includeadminBar.js" defer></script>

<!-- Sidebar fallback (إذا الملف الخارجي ما تحمّل) -->
<script>
    (async ()=>{
        const el = document.getElementById('sidebar-container');
        if (!el) return;
        try{
            const r = await fetch('sidebar.html');
            if (r.ok) { el.innerHTML = await r.text(); return; }
        }catch(e){}
        el.innerHTML = `
    <aside class="sidebar" style="padding:16px">
      <div class="logo" style="font-weight:800;font-size:22px;margin-bottom:16px">Matchy Matchy</div>
      <nav class="nav" style="display:grid;gap:10px">
        <a href="dashboard.html">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="users.html">Users</a>
        <a href="reviews.php" style="font-weight:700">Reviews</a>
        <a href="categories.php">Categories</a>
        <a href="settings.html">Settings</a>
      </nav>
    </aside>`;
    })();
</script>

<!-- mm-select (نفس ستايل القديم) + إمكانية refresh بعد تعبئة الخيارات -->
<script>
    function enhanceSelect(sel){
        if(sel.parentNode && sel.parentNode.classList && sel.parentNode.classList.contains('mm-select')) return;
        sel.classList.add('ui-select-hidden');

        const wrap=document.createElement('div');
        wrap.className='mm-select'; wrap.setAttribute('aria-expanded','false');

        const btn=document.createElement('button');
        btn.type='button'; btn.className='mm-select__btn';
        const label=document.createElement('span'); label.className='mm-select__label';
        const chev=document.createElement('i'); chev.className='mm-select__chev fa-solid fa-chevron-down';
        btn.append(label,chev);

        const menu=document.createElement('div'); menu.className='mm-select__menu';

        function rebuild(){
            menu.innerHTML='';
            Array.from(sel.options).forEach(opt=>{
                const row=document.createElement('button');
                row.type='button'; row.className='mm-option'; row.setAttribute('role','option'); row.dataset.value=opt.value;
                row.innerHTML=`<span>${opt.textContent}</span><i class="fa-solid fa-check" style="visibility:hidden"></i>`;
                if(opt.selected){ row.setAttribute('aria-selected','true'); row.querySelector('i').style.visibility='visible'; label.textContent=opt.textContent; }
                row.addEventListener('click', ()=>{
                    menu.querySelectorAll('.mm-option').forEach(o=>{o.setAttribute('aria-selected','false'); o.querySelector('i').style.visibility='hidden';});
                    row.setAttribute('aria-selected','true'); row.querySelector('i').style.visibility='visible';
                    label.textContent=opt.textContent; sel.value=opt.value;
                    sel.dispatchEvent(new Event('change',{bubbles:true})); close();
                });
                menu.appendChild(row);
            });
            if(!label.textContent) label.textContent = sel.options[sel.selectedIndex]?.text || sel.options[0]?.text || '';
        }
        function open(){ wrap.setAttribute('aria-expanded','true'); }
        function close(){ wrap.setAttribute('aria-expanded','false'); }
        function toggle(){ (wrap.getAttribute('aria-expanded')==='true')?close():open(); }

        btn.addEventListener('click',toggle);
        document.addEventListener('click',e=>{ if(!wrap.contains(e.target)) close(); });
        btn.addEventListener('keydown',e=>{ if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle();} if(e.key==='Escape'){close();} });
        menu.addEventListener('keydown',e=>{ if(e.key==='Escape'){ e.preventDefault(); close(); btn.focus(); } });

        sel.parentNode.insertBefore(wrap,sel);
        wrap.append(btn,menu,sel);
        rebuild();
        sel._mmRefresh = rebuild;
    }
    function enhanceAll(){ document.querySelectorAll('.toolbar .field select').forEach(enhanceSelect); }
</script>

<!-- Reviews logic -->
<script>
    (function(){
        const $  =(s,r=document)=>r.querySelector(s);
        const $$ =(s,r=document)=>Array.from(r.querySelectorAll(s));
        const esc=s=>String(s??'').replace(/[&<>"'`]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'}[c]));
        const hashColor=(str='?')=>{const cs=['#0ea5e9','#6366f1','#22c55e','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#10b981','#f97316'];let h=0;for(let i=0;i<str.length;i++){h=(h*31+str.charCodeAt(i))>>>0;}return cs[h%cs.length];};
        const firstLetter=u=>((u.first_name||u.email||'?').trim()[0]||'?').toUpperCase();
        const stars=n=>{n=Math.max(0,Math.min(5,Number(n)||0));return `<span class="stars">${'★'.repeat(n)}${'☆'.repeat(5-n)}</span>`};

        const qs=new URLSearchParams(location.search);
        let state={
            q:qs.get('q')??'',
            cat_id:qs.get('cat_id')??'',
            rating:qs.get('rating')??'',
            sort:qs.get('sort')??'new',
            hidden:qs.get('hidden')??'',
            flagged:qs.get('flagged')??'',
            page:Number(qs.get('page')||1),
            size:Number(qs.get('size')||10),
        };

        const qInput=$('#q'), catSel=$('#cat'), ratingSel=$('#rating'), sortSel=$('#sort'), alertBox=$('#alert');

        function showError(msg){ alertBox.textContent=`Failed to load reviews. ${msg}`; alertBox.style.display='block'; }
        function clearError(){ alertBox.style.display='none'; }

        function pushURL(){
            const u=new URL(location.href);
            for(const k of ['q','cat_id','rating','sort','hidden','flagged','page','size']){
                const v=state[k];
                if(v===''||v==null||(k==='page'&&v===1)) u.searchParams.delete(k); else u.searchParams.set(k,String(v));
            }
            history.replaceState(null,'',u);
        }

        // Parent categories
        async function loadParents(){
            catSel.innerHTML = `<option value="">All</option>`;
            try{
                const r=await fetch('/matchymatchy/backend/admin/categories-list.php',{headers:{Accept:'application/json'}});
                const j=await r.json();
                if(!j.ok) throw new Error(j.error||'categories error');
                (j.data||[]).forEach(c=>{
                    const o=document.createElement('option');
                    o.value=String(c.id); o.textContent=c.name;
                    catSel.appendChild(o);
                });
                catSel.value = state.cat_id || '';
                if(typeof catSel._mmRefresh==='function') catSel._mmRefresh();
            }catch(e){ console.warn('categories-list failed',e); }
        }

        function avatarHTML(u){
            if(u && u.avatar) return `<span class="mm-avatar"><img src="${esc(u.avatar)}" alt=""></span>`;
            const L=firstLetter(u||{}), bg=hashColor(u?.email||u?.first_name||L);
            return `<span class="mm-avatar" style="background:${bg}">${esc(L)}</span>`;
        }

        function rowHTML(r){
            const p=r.product||{}, u=r.user||{};
            const isHidden=!!r.hidden, isFlagged=!!r.flagged;
            const showReason=(state.flagged==='1')&&isFlagged&&r.flag_reason;

            const badge=isFlagged?`<span class="badge badge--flagged">Flagged</span>`
                :isHidden?`<span class="badge badge--hidden">Hidden</span>`
                    :`<span class="badge badge--visible">Visible</span>`;

            const actions=`
      ${isHidden?`<button class="btn" data-action="unhide" data-id="${r.id}"><i class="fa-regular fa-eye"></i> Unhide</button>`
                :`<button class="btn" data-action="hide" data-id="${r.id}"><i class="fa-solid fa-eye-slash"></i> Hide</button>`}
      ${isFlagged?`<button class="btn" data-action="unflag" data-id="${r.id}"><i class="fa-regular fa-flag"></i> Unflag</button>`
                :`<button class="btn" data-action="flag" data-id="${r.id}" data-prompt="true"><i class="fa-solid fa-flag"></i> Flag</button>`}
      <button class="btn btn-danger" style="background:#fee2e2;border-color:#fecaca;color:#991b1b" data-action="delete" data-id="${r.id}">
        <i class="fa-solid fa-trash"></i> Delete
      </button>`;

            return `
      <div class="review-item">
        <!-- الشارة مثبتة أعلى اليمين -->
        <div class="badge-pin">${badge}</div>

        <div class="review-head">
          <div class="left">
            <img class="thumb" src="${esc(p.image||'../images/placeholder.jpg')}" alt="" width="64" height="64" style="border-radius:12px;object-fit:cover">
            <div>
              <div style="font-weight:600">${esc(p.name||'—')}</div>
              <div class="muted" style="font-size:.85rem">SKU: ${esc(p.sku||'—')}</div>
              <div class="muted" style="font-size:.85rem">Matchy Matchy</div>
            </div>
          </div>
        </div>

        <div class="review-body" style="margin-top:10px">
          <div class="user" style="display:flex;align-items:center;gap:10px">
            ${avatarHTML(u)}
            <div>
              <div style="font-weight:600">${esc((u.first_name||'Anonymous') + (u.last_name ? ' ' + u.last_name[0] + '.' : ''))}</div>
              <div class="muted" style="font-size:.85rem">
                ${new Date(r.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}
                ${u.email ? ' • ' + esc(u.email) : ''}
              </div>
            </div>
          </div>

          <div class="stars" style="margin:6px 0">${stars(r.rating)}</div>
          <div style="margin:6px 0">${r.comment ? esc(r.comment) : '<em class="muted">No comment</em>'}</div>
          <div class="flag-reason ${showReason?'on':''}"><i class="fa-solid fa-flag"></i><div><strong>Flag reason:</strong> ${esc(r.flag_reason||'')}</div></div>

          <div class="actions" style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">${actions}</div>
        </div>
      </div>

      <hr style="border:none;border-top:1px solid #eee;margin:12px 0">
    `;
        }

        function renderList(json){
            const list=$('#reviewsList'), empty=$('#emptyState'), pagerInfo=$('#pagerInfo'), pageNums=$('#pageNums');
            if(!json.ok){ showError(json.error||'Unknown error'); return; }
            const rows=json.data||[];
            list.innerHTML=rows.map(rowHTML).join('');
            empty.style.display=rows.length?'none':'block';

            const total=Number(json.total||0), pages=Math.max(1,Math.ceil(total/state.size));
            pagerInfo.textContent=`Showing ${rows.length?((state.page-1)*state.size+1):0}-${Math.min(state.page*state.size,total)} of ${total}`;
            pageNums.innerHTML='';
            const start=Math.max(1,state.page-2), end=Math.min(pages,start+4);
            for(let i=start;i<=end;i++){
                const b=document.createElement('button');
                b.className='page-btn'+(i===state.page?' active':''); b.textContent=i;
                b.addEventListener('click',()=>{state.page=i;pushURL();load();});
                pageNums.appendChild(b);
            }
            $('#prevPage').disabled=state.page<=1;
            $('#nextPage').disabled=state.page>=pages;
        }

        async function load(){
            clearError();
            const params=new URLSearchParams({
                q:state.q, cat_id:state.cat_id||'', rating:state.rating, sort:state.sort,
                hidden:state.hidden, flagged:state.flagged, page:String(state.page), size:String(state.size)
            });
            try{
                const r=await fetch(`/matchymatchy/backend/admin/reviews.php?${params.toString()}`,{headers:{Accept:'application/json'}});
                if(!r.ok){ showError(`HTTP ${r.status}`); return; }
                const j=await r.json();
                renderList(j);
            }catch(e){ showError(e.message||'Network error'); }
        }

        async function updateReview(id,action,reason){
            try{
                const r=await fetch('/matchymatchy/backend/admin/reviews-update.php',{
                    method:'PATCH',
                    headers:{'Content-Type':'application/json','Accept':'application/json'},
                    body:JSON.stringify({id,action,reason:reason||''})
                });
                const j=await r.json();
                if(!j.ok) throw new Error(j.error||'update failed');
                await load();
            }catch(e){ showError(e.message); }
        }
        async function deleteReview(id){
            if(!confirm('Delete this review permanently?')) return;
            try{
                const r=await fetch('/matchymatchy/backend/admin/reviews-delete.php',{
                    method:'POST',
                    headers:{'Content-Type':'application/json','Accept':'application/json'},
                    body:JSON.stringify({id})
                });
                const j=await r.json();
                if(!j.ok) throw new Error(j.error||'delete failed');
                await load();
            }catch(e){ showError(e.message); }
        }

        // Actions
        $('#reviewsList').addEventListener('click', (e)=>{
            const btn=e.target.closest('button[data-action]'); if(!btn) return;
            const id=Number(btn.dataset.id), action=btn.dataset.action;
            if(action==='delete') return deleteReview(id);
            if(action==='flag' && btn.dataset.prompt){
                const reason=prompt('Flag reason (optional):','Inappropriate content');
                return updateReview(id,'flag',reason||'');
            }
            updateReview(id,action);
        });

        // Filters
        qInput.value=state.q; ratingSel.value=state.rating; sortSel.value=state.sort;
        qInput.addEventListener('keydown',(e)=>{ if(e.key==='Enter'){ state.q=qInput.value.trim(); state.page=1; pushURL(); load(); }});
        qInput.addEventListener('blur',()=>{ if(state.q!==qInput.value.trim()){ state.q=qInput.value.trim(); state.page=1; pushURL(); load(); }});
        catSel.addEventListener('change',()=>{ state.cat_id=catSel.value||''; state.page=1; pushURL(); load(); });
        ratingSel.addEventListener('change',()=>{ state.rating=ratingSel.value; state.page=1; pushURL(); load(); });
        sortSel.addEventListener('change',()=>{ state.sort=sortSel.value||'new'; state.page=1; pushURL(); load(); });

        $('#clearFilters').addEventListener('click',()=>{
            state={ q:'', cat_id:'', rating:'', sort:'new', hidden:'', flagged:'', page:1, size:state.size };
            qInput.value=''; catSel.value=''; if(typeof catSel._mmRefresh==='function') catSel._mmRefresh();
            ratingSel.value=''; sortSel.value='new';
            $$('#statusTabs .pill').forEach(b=>b.classList.remove('active'));
            $$('#statusTabs .pill')[0].classList.add('active');
            pushURL(); load();
        });

        $('#prevPage').addEventListener('click',()=>{ if(state.page>1){ state.page--; pushURL(); load(); }});
        $('#nextPage').addEventListener('click',()=>{ state.page++; pushURL(); load(); });

        $('#statusTabs').addEventListener('click',(e)=>{
            const b=e.target.closest('.pill'); if(!b) return;
            $$('#statusTabs .pill').forEach(x=>x.classList.remove('active')); b.classList.add('active');
            const mode=b.dataset.mode;
            if(mode==='all'){ state.hidden=''; state.flagged=''; }
            if(mode==='visible'){ state.hidden='0'; state.flagged=''; }
            if(mode==='hidden'){ state.hidden='1'; state.flagged=''; }
            if(mode==='flagged'){ state.hidden=''; state.flagged='1'; }
            state.page=1; pushURL(); load();
        });

        // Init
        window.addEventListener('DOMContentLoaded', async ()=>{
            enhanceAll();       // فعّل mm-select
            await loadParents();// عبّي الفئات

            const mode= state.flagged==='1'?'flagged':(state.hidden==='1'?'hidden':(state.hidden==='0'?'visible':'all'));
            const active=document.querySelector(`#statusTabs .pill[data-mode="${mode}"]`); if(active) active.classList.add('active');

            pushURL();
            load();
        });
    })();
</script>
</body>
</html>
