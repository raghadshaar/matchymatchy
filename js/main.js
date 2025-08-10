document.addEventListener('DOMContentLoaded', function() {
    const profileBtn = document.querySelector('.profile-btn');
    const profileMenu = document.getElementById('profile-menu');
    const notifyBtn = document.querySelector('.notify-btn');
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