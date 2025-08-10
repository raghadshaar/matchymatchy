
window.addEventListener('DOMContentLoaded', () => {
    fetch('sidebar.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('sidebar-container').innerHTML = data;
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('href') === currentPage) {
                    item.classList.add('active');
                }
            });
        });
});
window.addEventListener('DOMContentLoaded', () => {
    const profileBtn = document.querySelector('.profile-btn');
    const profileMenu = document.getElementById('profile-menu');
    const notifyBtn  = document.querySelector('.notify-btn');
    const notifyMenu = document.getElementById('notify-menu');

    function closeAll(){
        profileMenu?.classList.remove('show');
        notifyMenu?.classList.remove('show');
        profileBtn?.setAttribute('aria-expanded','false');
        notifyBtn?.setAttribute('aria-expanded','false');
    }
    function toggle(menu, btn){
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
});




window.addEventListener('DOMContentLoaded', () => {
    const profileBtn   = document.querySelector('.profile-btn');
    const menu         = document.getElementById('profile-menu');
    const btnImg       = document.querySelector('.pf-avatar img');
    const btnNameEl    = document.querySelector('.pf-meta strong');
    const btnRoleEl    = document.querySelector('.pf-meta small');
    const emailEl      = document.querySelector('.pf-email');

    const switchBtn    = document.querySelector('.pf-account');
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

        menu?.classList.remove('show');
        profileBtn?.setAttribute('aria-expanded','false');
    });
});