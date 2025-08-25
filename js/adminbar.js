    (function () {
    // ========== Bind Topbar ==========
    function bindTopbar(root = document) {
        const topbar = root.querySelector('.topbar');
        if (!topbar || topbar.dataset.bound) return;
        topbar.dataset.bound = '1';

        const notifyBtn  = topbar.querySelector('.notify-btn');
        const notifyMenu = topbar.querySelector('#notify-menu');

        function closeAll() { notifyMenu?.classList.remove('show'); notifyBtn?.setAttribute('aria-expanded','false'); }
        function toggle(menu, btn) {
            const isOpen = menu.classList.toggle('show');
            btn?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        notifyBtn?.addEventListener('click', (e)=>{
            e.stopPropagation();
            toggle(notifyMenu, notifyBtn);
        });
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeAll(); });

        // ========== Notifications ==========
        const NOTIFY_LS_KEY = 'mm_notify_v1';
        const notifyList   = topbar.querySelector('#notifyList');
        const markAllBtn   = topbar.querySelector('#markAllRead');
        const clearAllBtn  = topbar.querySelector('#clearAll');
        const bellBadgeDot = topbar.querySelector('.notify-btn .badge-dot');

        const notifySeed = [
            { id: 1, type:'order',   title:'New order #1045', text:'Customer placed a new order.', ts:Date.now()-5*60*1000, unread:true },
            { id: 2, type:'stock',   title:'Low stock: PJ-FAM-001', text:'Only 3 items left.', ts:Date.now()-60*60*1000, unread:true },
            { id: 3, type:'message', title:'New review', text:'⭐️⭐️⭐️⭐️ from Lina', ts:Date.now()-5*60*60*1000, unread:false }
        ];

        function nLoad(){
            try{ return JSON.parse(localStorage.getItem(NOTIFY_LS_KEY)) || notifySeed; }
            catch{ return notifySeed; }
        }
        function nSave(arr){ localStorage.setItem(NOTIFY_LS_KEY, JSON.stringify(arr)); }
        let notifyItems = nLoad();

        function nTimeAgo(ts){
            const diff = Math.floor((Date.now()-ts)/1000);
            if(diff<60) return `${diff}s ago`;
            const m = Math.floor(diff/60); if(m<60) return `${m}m ago`;
            const h = Math.floor(m/60); if(h<24) return `${h}h ago`;
            return `${Math.floor(h/24)}d ago`;
        }
        function nIcon(type){
            if(type==='order')   return '<i class="fa-solid fa-bag-shopping fa-sm"></i>';
            if(type==='stock')   return '<i class="fa-solid fa-boxes-stacked fa-sm"></i>';
            if(type==='message') return '<i class="fa-regular fa-message fa-sm"></i>';
            return '<i class="fa-regular fa-bell fa-sm"></i>';
        }
        function nUpdateBadge(){
            const hasUnread = notifyItems.some(x=>x.unread);
            if(bellBadgeDot) bellBadgeDot.style.display = hasUnread ? 'block' : 'none';
        }
        function nRender(){
            if(!notifyList) return;
            if(notifyItems.length===0){
                notifyList.innerHTML = `<li class="notify-item">No notifications 🎉</li>`;
                return nUpdateBadge();
            }
            notifyList.innerHTML = notifyItems.map(n => `
              <li class="notify-item ${n.unread?'unread':''}" data-id="${n.id}">
                <div class="notify-ic">${nIcon(n.type)}</div>
                <div class="notify-body">
                  <div class="notify-title">${n.title}</div>
                  <div class="notify-meta">${n.text} • ${nTimeAgo(n.ts)}</div>
                  <div class="notify-actions-row">
                    <button class="link-btn markRead">${n.unread?'Mark as read':'Mark as unread'}</button>
                    <button class="link-btn del">Delete</button>
                  </div>
                </div>
              </li>
            `).join('');

            // Bind actions
            notifyList.querySelectorAll('.markRead').forEach(btn=>{
                btn.onclick = ()=>{
                    const id = +btn.closest('.notify-item').dataset.id;
                    const it = notifyItems.find(x=>x.id===id);
                    if(it){ it.unread=!it.unread; nSave(notifyItems); nRender(); }
                };
            });
            notifyList.querySelectorAll('.del').forEach(btn=>{
                btn.onclick = ()=>{
                    const id = +btn.closest('.notify-item').dataset.id;
                    notifyItems = notifyItems.filter(x=>x.id!==id);
                    nSave(notifyItems); nRender();
                };
            });
            nUpdateBadge();
        }

        markAllBtn?.addEventListener('click', ()=>{ notifyItems.forEach(i=> i.unread=false); nSave(notifyItems); nRender(); });
        clearAllBtn?.addEventListener('click', ()=>{ if(confirm('Clear all?')){ notifyItems=[]; nSave(notifyItems); nRender(); } });

        nRender();

        window.pushNotification = function(n){
            notifyItems.unshift({ id:Date.now(), ts:Date.now(), unread:true, ...n });
            nSave(notifyItems); nRender();
        };
    }

    document.addEventListener('DOMContentLoaded', () => bindTopbar(document));
    document.addEventListener('admin:include:done', e => bindTopbar(e.detail?.root||document));
    window.initTopbar = () => bindTopbar(document);
})();

    (async function hydrateUserMini(){
        let avatar, meta, nameEl, roleEl, bLogin, bSet, bOut;

        try {
            const res = await fetch('/matchymatchy/backend/info.php', { credentials:'include' });
            const j = await res.json();

            avatar = document.getElementById('user-avatar');
            meta   = document.getElementById('user-meta');
            nameEl = document.getElementById('user-name');
            roleEl = document.getElementById('user-role');
            bLogin = document.getElementById('btn-login');
            bSet   = document.getElementById('btn-settings');
            bOut   = document.getElementById('btn-logout');

            if (!j.ok || !j.is_logged) {
                if (bLogin) bLogin.style.display = 'inline-flex';
                return;
            }

            if (avatar) {
                avatar.src = j.avatar || 'https://ui-avatars.com/api/?name=User&background=008080&color=fff';
                avatar.style.display = 'block';
            }
            if (nameEl) nameEl.textContent = j.name || 'User';
            if (roleEl) roleEl.textContent = j.role || 'Customer';
            if (meta)   meta.style.display = 'block';
            if (bSet)   bSet.style.display = 'inline-flex';
            if (bOut)   bOut.style.display = 'inline-flex';
        } catch {
            const bLoginEl = document.getElementById('btn-login');
            if (bLoginEl) bLoginEl.style.display = 'inline-flex';
        }

        if (bOut) {
            bOut.addEventListener('click', async (e)=>{
                e.preventDefault();
                try {
                    await fetch('/matchymatchy/backend/logout.php', { method:'POST', credentials:'include' });
                } catch(_) {}
                location.href = '/matchymatchy/sign-in.php';
            });
        }
    })();
