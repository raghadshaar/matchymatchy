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
            const isOpen = menu.classList.toggle('show');
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

        // Account Switch
        const btnImg       = topbar.querySelector('.pf-avatar img');
        const btnNameEl    = topbar.querySelector('.pf-meta strong');
        const btnRoleEl    = topbar.querySelector('.pf-meta small');
        const emailEl      = topbar.querySelector('.pf-email');

        const switchBtn    = topbar.querySelector('.pf-account');
        const swImg        = switchBtn?.querySelector('.pf-acc-avatar img');
        const swNameEl     = switchBtn?.querySelector('.pf-acc-meta strong');
        const swRoleEl     = switchBtn?.querySelector('.pf-acc-meta small');

        switchBtn?.addEventListener('click', (e) => {
            e.preventDefault();

            const current = {
                name:  btnNameEl.textContent.trim(),
                role:  btnRoleEl.textContent.trim(),
                email: emailEl.textContent.trim(),
                img:   btnImg.getAttribute('src')
            };

            const alt = {
                name:  swNameEl.textContent.trim(),
                role:  swRoleEl.textContent.trim(),
                email: switchBtn.dataset.email || current.email,
                img:   swImg.getAttribute('src')
            };

            btnNameEl.textContent = alt.name;
            btnRoleEl.textContent = alt.role;
            emailEl.textContent   = alt.email;
            emailEl.setAttribute('href', 'mailto:' + alt.email);
            btnImg.setAttribute('src', alt.img);

            swNameEl.textContent = current.name;
            swRoleEl.textContent = current.role;
            swImg.setAttribute('src', current.img);
            switchBtn.dataset.email = current.email;

            profileMenu?.classList.remove('show');
            profileBtn?.setAttribute('aria-expanded','false');
        });
    }

    document.addEventListener('DOMContentLoaded', () => bindTopbar(document));

    document.addEventListener('admin:include:done', (e) => {
        const root = (e.detail && e.detail.root) || document;
        bindTopbar(root);
    });

    window.initTopbar = () => bindTopbar(document);
})();
