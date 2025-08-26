

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
