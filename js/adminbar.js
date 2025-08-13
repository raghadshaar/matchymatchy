(function () {
    function bindTopbar(root = document) {
        const topbar = root.querySelector('.topbar');
        if (!topbar || topbar.dataset.bound) return;
        topbar.dataset.bound = '1';

        const profileBtn = topbar.querySelector('.profile-btn');
        const profileMenu = topbar.querySelector('#profile-menu');
        const notifyBtn  = topbar.querySelector('.notify-btn');
        const notifyMenu = topbar.querySelector('#notify-menu');

        // فتح/إغلاق القوائم
        function closeAll() {
            profileMenu?.classList.remove('show');
            notifyMenu?.classList.remove('show');
            profileBtn?.setAttribute('aria-expanded','false');
            notifyBtn?.setAttribute('aria-expanded','false');
        }
        function toggle(menu, btn) {
            const isOpen = menu.classList.toggle('show'); // اعتمد "show" ثابت
            btn?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        profileBtn?.addEventListener('click', (e)=>{
            e.stopPropagation();
            notifyMenu?.classList.remove('show');
            toggle(profileMenu, profileBtn);
        });

        notifyBtn?.addEventListener('click', (e)=>{
            e.stopPropagation();
            profileMenu?.classList.remove('show');
            toggle(notifyMenu, notifyBtn);
        });

        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeAll(); });

        // ===== Notifications (انقل كل منطق الإشعارات لداخل bindTopbar حتى يلاقي عناصره) =====
        const NOTIFY_LS_KEY = 'mm_notify_v1';
        const notifyList   = topbar.querySelector('#notifyList');
        const markAllBtn   = topbar.querySelector('#markAllRead');
        const clearAllBtn  = topbar.querySelector('#clearAll');
        const bellBadgeDot = topbar.querySelector('.notify-btn .badge-dot');

        const notifySeed = [
            { id: 1, type:'order',   title:'New order #1045', text:'Customer placed a new order.',   ts: Date.now()- 5*60*1000,  unread:true },
            { id: 2, type:'stock',   title:'Low stock: PJ-FAM-001', text:'Only 3 items left in stock.', ts: Date.now()-60*60*1000,  unread:true },
            { id: 3, type:'message', title:'New review on product', text:'⭐️⭐️⭐️⭐️ from Lina',           ts: Date.now()- 5*60*60*1000, unread:false },
        ];

        function nLoad(){
            const raw = localStorage.getItem(NOTIFY_LS_KEY);
            if(!raw){ localStorage.setItem(NOTIFY_LS_KEY, JSON.stringify(notifySeed)); return notifySeed.slice(); }
            try { return JSON.parse(raw); } catch { return notifySeed.slice(); }
        }
        function nSave(arr){ localStorage.setItem(NOTIFY_LS_KEY, JSON.stringify(arr)); }
        let notifyItems = nLoad();

        function nTimeAgo(ts){
            const diff = Math.floor((Date.now()-ts)/1000);
            if(diff<60) return `${diff}s ago`;
            const m = Math.floor(diff/60); if(m<60) return `${m}m ago`;
            const h = Math.floor(m/60); if(h<24) return `${h}h ago`;
            const d = Math.floor(h/24); return `${d}d ago`;
        }
        function nIcon(type){
            if(type==='order')   return '<i class="fa-solid fa-bag-shopping fa-sm"></i>';
            if(type==='stock')   return '<i class="fa-solid fa-boxes-stacked fa-sm"></i>';
            if(type==='message') return '<i class="fa-regular fa-message fa-sm"></i>';
            return '<i class="fa-regular fa-bell fa-sm"></i>';
        }
        function nUpdateBadge(){
            const hasUnread = notifyItems.some(x=>x.unread);
            if(bellBadgeDot){ bellBadgeDot.style.display = hasUnread ? 'block' : 'none'; }
        }
        function nRender(){
            if(!notifyList) return;
            if(notifyItems.length===0){
                notifyList.innerHTML = `
          <li class="notify-item" style="border:1px solid var(--line);border-radius:12px;padding:10px;background:#fff;">
            <div class="notify-body">
              <div class="notify-title" style="font-weight:600;">No notifications</div>
              <div class="notify-meta" style="color:var(--ink-soft);font-size:.9rem;">You’re all caught up 🎉</div>
            </div>
          </li>`;
                return nUpdateBadge();
            }
            notifyList.innerHTML = notifyItems.map(n => `
        <li class="notify-item ${n.unread?'unread':''}" data-id="${n.id}"
            style="display:flex;gap:10px;padding:10px;border-radius:12px;border:1px solid var(--line);background:#fff;margin-bottom:8px;${n.unread?'background:#fbfaf7;':''}">
          <div class="notify-ic" style="width:36px;height:36px;display:grid;place-items:center;border-radius:10px;background:#f3f6f7;color:var(--teal);">
            ${nIcon(n.type)}
          </div>
          <div class="notify-body" style="flex:1;">
            <div class="notify-title" style="font-weight:600;">${n.title}</div>
            <div class="notify-meta" style="color:var(--ink-soft);font-size:.9rem;">${n.text} • ${nTimeAgo(n.ts)}</div>
            <div class="notify-actions-row" style="display:flex;gap:10px;margin-top:6px;">
              <button type="button" class="link-btn markRead" style="background:none;border:0;color:var(--teal);cursor:pointer;">
                ${n.unread?'Mark as read':'Mark as unread'}
              </button>
              <button type="button" class="link-btn del" style="background:none;border:0;color:#b94a48;cursor:pointer;">Delete</button>
            </div>
          </div>
        </li>
      `).join('');

            notifyList.querySelectorAll('.markRead').forEach(btn=>{
                btn.addEventListener('click', (e)=>{
                    const id = +e.target.closest('.notify-item').dataset.id;
                    const it = notifyItems.find(x=>x.id===id);
                    it.unread = !it.unread; nSave(notifyItems); nRender();
                });
            });
            notifyList.querySelectorAll('.del').forEach(btn=>{
                btn.addEventListener('click', (e)=>{
                    const id = +e.target.closest('.notify-item').dataset.id;
                    notifyItems = notifyItems.filter(x=>x.id!==id);
                    nSave(notifyItems); nRender();
                });
            });

            notifyList.querySelectorAll('.notify-item').forEach(row=>{
                row.addEventListener('click', (e)=>{
                    if(e.target.closest('.link-btn')) return;
                    const id = +row.dataset.id;
                    const it = notifyItems.find(x=>x.id===id);
                    if(it && it.unread){ it.unread=false; nSave(notifyItems); nRender(); }
                });
            });

            nUpdateBadge();
        }

        markAllBtn?.addEventListener('click', ()=>{
            notifyItems.forEach(i=> i.unread=false); nSave(notifyItems); nRender();
        });
        clearAllBtn?.addEventListener('click', ()=>{
            if(confirm('Clear all notifications?')){ notifyItems = []; nSave(notifyItems); nRender(); }
        });

        nRender();

        // API عام لإضافة إشعار من أي سكربت
        window.pushNotification = function pushNotification(n){
            const it = { id: Date.now(), type:'order', title:'New notification', text:'...', ts:Date.now(), unread:true, ...n };
            notifyItems.unshift(it);
            nSave(notifyItems);
            nRender();
        };

        // ===== ربط البحث من التوب بار =====
        // ملاحظة: عندك بالصفحة search سفلي (#searchInput) شغال. هنا نربط التوب بار بنفس state/render.
        // ندعم كلا الكلاسين: .top-search أو .search
        const searchInput =
            topbar.querySelector('.top-search input') ||
            topbar.querySelector('.search input');

        // امنع إرسال الفورم (رفرش)
        const searchForm =
            topbar.querySelector('.top-search') ||
            topbar.querySelector('.search');
        searchForm?.addEventListener('submit', e => e.preventDefault());

        searchInput?.addEventListener('input', (e) => {
            const q = e.target.value.trim();

            // لو صفحة المنتجات معرّفة state/render عالمياً
            if (window.state && typeof window.render === 'function') {
                window.state.q = q;
                window.state.page = 1;
                window.render();
            } else {
                // بديل عام: ابعث حدث عام تسمعه صفحة المنتجات وتفلتر
                const ev = new CustomEvent('topbar:search', { detail: { q } });
                window.dispatchEvent(ev);
            }
        });
    }

    // شغّل الربط بعد تحميل DOM
    document.addEventListener('DOMContentLoaded', () => bindTopbar(document));

    // وشغّله كمان بعد ما يخلص include (حسب سكربتك)
    document.addEventListener('admin:include:done', (e) => {
        const root = (e.detail && e.detail.root) || document;
        bindTopbar(root);
    });

    // إكسپورت لو حبيتي تناديه يدوياً
    window.initTopbar = () => bindTopbar(document);
})();
