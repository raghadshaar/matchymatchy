
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Matchy Matchy</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>

        :root {
            --baby: #F9F8F3;
            --jet: #383838;
            --teal: #008080;
            --pink: #E4CFC3;
            --umber: #715E51;
            --white: #ffffff;
        }
        .pattern-bg {
            background-image: radial-gradient(circle at 20% 20%, var(--pink) 0%, transparent 20%),
            radial-gradient(circle at 80% 80%, var(--pink) 0%, transparent 20%),
            radial-gradient(circle at 40% 60%, var(--pink) 0%, transparent 20%);
            background-size: 100px 100px, 120px 120px, 80px 80px;
            background-position: 0 0, 40px 40px, 80px 20px;
        }
        .strength-bar {
            transition: all 0.3s ease;
        }
        .strength-weak { background-color: #ef4444; width: 25%; }
        .strength-fair { background-color: #f59e0b; width: 50%; }
        .strength-good { background-color: #10b981; width: 75%; }
        .strength-strong { background-color: var(--teal); width: 100%; }
        .google-btn {
            background: linear-gradient(135deg, #4285f4 0%, #34a853 25%, #fbbc05 50%, #ea4335 75%);
            background-size: 400% 400%;
            animation: gradient 3s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .custom-checkbox {
            appearance: none;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid var(--umber);
            border-radius: 0.25rem;
            background: var(--white);
            position: relative;
            cursor: pointer;
        }
        .custom-checkbox:checked {
            background: var(--teal);
            border-color: var(--teal);
        }
        .custom-checkbox:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 0.875rem;
            font-weight: bold;
        }
        .floating-label {
            transition: all 0.2s ease;
        }
        .input-focused .floating-label {
            transform: translateY(-1.5rem) scale(0.875);
            color: var(--teal);
        }
        .auth-card {
            background: var(--white);
            border: 1px solid rgba(113, 94, 81, 0.1);
            box-shadow: 0 10px 25px rgba(113, 94, 81, 0.1);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#008080',
                        secondary: '#715E51'
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
</head>
<body style="background-color: var(--baby);" class="min-h-screen pattern-bg">
<?php if (!empty($_GET['notice'])): ?>
    <div class="mb-4 rounded-lg border px-4 py-3 flex items-center gap-2"
         style="border-color:#E4CFC3;background:#F9F8F3;color:red;">
        <i class="ri-information-line"></i>
        <span><?= htmlspecialchars($_GET['notice'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<div class="min-h-screen flex flex-col">
    <header class="py-6">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <h1 class="font-['Pacifico'] text-4xl" style="color: var(--teal);">Matchy Matchy</h1>
                <p class="text-sm mt-2" style="color: var(--umber);">Adorable Outfits for Little Ones</p>
            </div>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <div class="auth-card rounded-2xl p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold mb-2" style="color: var(--jet);">Welcome Back</h2>
                    <p class="text-sm" style="color: var(--umber);">Sign in to your account to continue shopping</p>
                </div>

                <!-- SIGN IN (single valid form) -->
                <form id="signin-form" class="space-y-6">
                    <div class="relative">
                        <input type="email" id="email" name="email"
                               class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors peer"
                               style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="email" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">Email Address</label>
                        <div class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-mail-line text-lg" style="color: var(--umber);"></i>
                        </div>
                    </div>

                    <div class="relative">
                        <input type="password" id="password" name="password"
                               class="w-full px-4 py-3 pr-12 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors"
                               style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="password" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">Password</label>
                        <button type="button" id="toggle-password" class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-eye-line text-lg" style="color: var(--umber);"></i>
                        </button>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" id="remember-me" class="custom-checkbox">
                            <span class="text-sm" style="color: var(--umber);">Remember me</span>
                        </label>
                        <button type="button" id="forgot-password-btn" class="text-sm hover:underline" style="color: var(--teal);">Forgot Password?</button>
                    </div>

                    <button type="submit" id="signin-submit"
                            class="w-full py-3 rounded-button font-semibold text-white transition-all hover:opacity-90 whitespace-nowrap"
                            style="background-color: var(--teal);">
                        Sign In
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t" style="border-color: var(--pink);"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4" style="background-color: var(--white); color: var(--umber);">Or continue with</span>
                    </div>
                </div>

                <!-- Google Sign-In (separate file for sign-in flow) -->
                <a href="/matchymatchy/PHP/google-login-signin.php"
                   class="w-full py-3 rounded-button font-semibold text-white google-btn flex items-center justify-center space-x-2">
                    <i class="ri-google-fill text-lg"></i>
                    <span>Continue with Google</span>
                </a>


                <div class="text-center mt-6">
                    <p class="text-sm" style="color: var(--umber);">
                        Don't have an account?
                        <a href="/matchymatchy/HTML/signup.html" class="font-semibold hover:underline" style="color: var(--teal);">Sign up</a>
                    </p>
                </div>

                <!-- FORGOT PASSWORD (send link) -->
                <div id="forgot-password-form" class="space-y-6 hidden mt-8">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold mb-2" style="color: var(--jet);">Reset Password</h3>
                        <p class="text-sm" style="color: var(--umber);">Enter your email address and we'll send you a reset link</p>
                    </div>
                    <div class="relative">
                        <input type="email" id="reset-email"
                               class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors"
                               style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="reset-email" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">Email Address</label>
                        <div class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-mail-line text-lg" style="color: var(--umber);"></i>
                        </div>
                    </div>
                    <button type="button" id="reset-link-button"
                            class="w-full py-3 rounded-button font-semibold text-white transition-all hover:opacity-90 whitespace-nowrap flex items-center justify-center"
                            style="background-color: var(--teal);">
                        <span id="reset-button-text">Send Reset Link</span>
                        <span id="reset-button-spinner" class="hidden ml-2">
              <i class="ri-loader-4-line animate-spin"></i>
            </span>
                    </button>
                    <button type="button" id="back-to-signin" class="w-full py-2 text-sm hover:underline" style="color: var(--umber);">
                        Back to Sign In
                    </button>
                </div>

                <!-- RESET PASSWORD (shown when ?token=... in URL) -->
                <div id="reset-password-form" class="space-y-6 hidden mt-8">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold mb-2" style="color: var(--jet);">Create New Password</h3>
                        <p class="text-sm" style="color: var(--umber);">Please enter your new password below</p>
                    </div>

                    <div class="relative">
                        <input type="password" id="new-password"
                               class="w-full px-4 py-3 pr-12 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors"
                               style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="new-password" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">New Password</label>
                        <button type="button" id="toggle-new-password" class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-eye-line text-lg" style="color: var(--umber);"></i>
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs" style="color: var(--umber);">Password Strength</span>
                            <span id="strength-text" class="text-xs font-semibold" style="color: var(--umber);">Weak</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="strength-bar" class="strength-bar h-2 rounded-full strength-weak"></div>
                        </div>
                    </div>

                    <div class="text-xs space-y-1" style="color: var(--umber);">
                        <p id="req-length" class="flex items-center space-x-2">
                            <i class="ri-close-circle-line text-red-500"></i><span>At least 8 characters</span>
                        </p>
                        <p id="req-uppercase" class="flex items-center space-x-2">
                            <i class="ri-close-circle-line text-red-500"></i><span>One uppercase letter</span>
                        </p>
                        <p id="req-lowercase" class="flex items-center space-x-2">
                            <i class="ri-close-circle-line text-red-500"></i><span>One lowercase letter</span>
                        </p>
                        <p id="req-number" class="flex items-center space-x-2">
                            <i class="ri-close-circle-line text-red-500"></i><span>One number</span>
                        </p>
                    </div>

                    <div class="relative">
                        <input type="password" id="confirm-password"
                               class="w-full px-4 py-3 pr-12 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors"
                               style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="confirm-password" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">Confirm Password</label>
                        <button type="button" id="toggle-confirm-password" class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-eye-line text-lg" style="color: var(--umber);"></i>
                        </button>
                    </div>

                    <div id="password-match" class="text-xs hidden">
                        <p class="flex items-center space-x-2 text-red-500">
                            <i class="ri-close-circle-line"></i><span>Passwords do not match</span>
                        </p>
                    </div>

                    <button id="reset-submit"
                            class="w-full py-3 rounded-button font-semibold text-white transition-all hover:opacity-90 whitespace-nowrap opacity-50 cursor-not-allowed"
                            style="background-color: var(--teal);" disabled>
                        Reset Password
                    </button>
                </div>

                <!-- Success after sending reset link -->
                <div id="success-message" class="text-center space-y-4 hidden mt-8">
                    <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center" style="background-color: var(--teal);">
                        <i class="ri-check-line text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold" style="color: var(--jet);">Check Your Email</h3>
                    <p class="text-sm" style="color: var(--umber);">We've sent a password reset link to your email address. Please check your inbox and follow the instructions.</p>
                    <button type="button" id="back-to-signin-success" class="text-sm hover:underline" style="color: var(--teal);">Back to Sign In</button>
                </div>
            </div>

            <!-- Little badges -->
            <div class="text-center mt-6">
                <div class="flex items-center justify-center space-x-4 text-xs" style="color: var(--umber);">
                    <div class="flex items-center space-x-1">
                        <i class="ri-shield-check-line"></i><span>Secure Login</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <i class="ri-lock-line"></i><span>SSL Protected</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <i class="ri-user-heart-line"></i><span>Privacy First</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ===== SCRIPTS (no duplicates) ===== -->

<script id="form-navigation">
    document.addEventListener('DOMContentLoaded', function() {
        const signinForm = document.getElementById('signin-form');
        const forgotForm = document.getElementById('forgot-password-form');
        const resetForm  = document.getElementById('reset-password-form');
        const successMsg = document.getElementById('success-message');

        const forgotBtn  = document.getElementById('forgot-password-btn');
        const backBtn    = document.getElementById('back-to-signin');
        const backSucc   = document.getElementById('back-to-signin-success');

        function showForm(el) {
            [signinForm, forgotForm, resetForm, successMsg].forEach(x => x && x.classList.add('hidden'));
            el && el.classList.remove('hidden');
        }

        if (forgotBtn) forgotBtn.addEventListener('click', () => showForm(forgotForm));
        if (backBtn)   backBtn.addEventListener('click', () => showForm(signinForm));
        if (backSucc)  backSucc.addEventListener('click', () => showForm(signinForm));
    });
</script>

<script id="password-visibility">
    document.addEventListener('DOMContentLoaded', function() {
        function toggle(btnId, inputId) {
            const btn = document.getElementById(btnId);
            const inp = document.getElementById(inputId);
            if (!btn || !inp) return;
            btn.addEventListener('click', () => {
                const icon = btn.querySelector('i');
                if (inp.type === 'password') { inp.type = 'text';  if (icon) icon.className = 'ri-eye-off-line text-lg'; }
                else                         { inp.type = 'password'; if (icon) icon.className = 'ri-eye-line text-lg'; }
            });
        }
        toggle('toggle-password', 'password');
        toggle('toggle-new-password', 'new-password');
        toggle('toggle-confirm-password', 'confirm-password');
    });
</script>

<script id="floating-labels">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[type="email"], input[type="password"]').forEach(input => {
            const container = input.parentElement;
            function update() {
                if (input.value.trim() !== '' || input === document.activeElement) container.classList.add('input-focused');
                else container.classList.remove('input-focused');
            }
            ['focus','blur','input'].forEach(ev => input.addEventListener(ev, update));
            update();
        });
    });
</script>

<script id="signin-submit">
    document.addEventListener('DOMContentLoaded', () => {
        const form   = document.getElementById('signin-form');
        const email  = document.getElementById('email');
        const pass   = document.getElementById('password');
        const remember = document.getElementById('remember-me');
        const btn    = document.getElementById('signin-submit');

        function fmtRemain(secs){ const m=Math.floor(secs/60),s=secs%60; return `${m}m ${String(s).padStart(2,'0')}s`; }
        async function postForm(url, payload){
            const res = await fetch(url, {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                credentials:'same-origin',
                body:new URLSearchParams(payload)
            });
            let json={}; try{ json = await res.json(); }catch(_){}
            return {res,json};
        }
        function setLoading(on){
            if(!btn) return;
            if(on){ btn.dataset._html = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Signing in...'; }
            else  { btn.disabled = false; if(btn.dataset._html) btn.innerHTML = btn.dataset._html; }
        }

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const eVal = (email?.value || '').trim();
                const pVal = (pass?.value || '');
                if (!eVal || !pVal) return;

                if (remember?.checked) localStorage.setItem('rememberedEmail', eVal);
                else localStorage.removeItem('rememberedEmail');

                setLoading(true);
                try {
                    const {res, json} = await postForm('/matchymatchy/api/login.php', { email: eVal, password: pVal });
                    if (res.ok && json.ok) { location.href = json.redirect || '/matchymatchy/HTML/index.html'; return; }

                    if (res.status === 404 && json.error === 'no_account') {
                        alert('No account found for this email. Please sign up.');
                        location.href = '/matchymatchy/HTML/signup.html';
                        return;
                    }
                    if (res.status === 429 && json.error === 'locked') {
                        alert(`Too many attempts. Try again in ${fmtRemain(Number(json.lock_remaining_sec||0))}.`);
                        return;
                    }
                    alert(json.message || 'Incorrect email or password.');
                } catch {
                    alert('Network error. Please try again.');
                } finally {
                    setLoading(false);
                }
            });

            // restore remembered
            const saved = localStorage.getItem('rememberedEmail');
            if (saved && email) {
                email.value = saved;
                email.parentElement?.classList.add('input-focused');
                if (remember) remember.checked = true;
            }
        }
    });
</script>

<script id="forgot-send-link">
    document.addEventListener('DOMContentLoaded', () => {
        const block   = document.getElementById('forgot-password-form');
        const btn     = document.getElementById('reset-link-button');
        const email   = document.getElementById('reset-email');
        const txt     = document.getElementById('reset-button-text');
        const spin    = document.getElementById('reset-button-spinner');
        const success = document.getElementById('success-message');

        async function postForm(url, payload){
            const res = await fetch(url, {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                credentials:'same-origin',
                body:new URLSearchParams(payload)
            });
            let json={}; try{ json = await res.json(); }catch(_){}
            return {res,json};
        }

        if (btn && email && block) {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const val = (email.value || '').trim();
                if (!val) { email.classList.add('border-red-500'); return; }
                email.classList.remove('border-red-500');

                btn.disabled = true;
                txt.textContent = 'Sending...';
                spin.classList.remove('hidden');

                try {
                    const {res, json} = await postForm('/matchymatchy/api/request-password-reset.php', { email: val });

                    if (res.status === 404 && json.error === 'no_account') {
                        alert('No account found for this email. Please sign up.');
                        location.href = '/matchymatchy/HTML/signup.html';
                        return;
                    }

                    // success or neutral -> show confirmation
                    block.classList.add('hidden');
                    success?.classList.remove('hidden');
                } catch {
                    alert('Something went wrong. Please try again.');
                } finally {
                    btn.disabled = false;
                    txt.textContent = 'Send Reset Link';
                    spin.classList.add('hidden');
                }
            });
        }
    });
</script>

<script id="inline-reset-flow">
    document.addEventListener('DOMContentLoaded', function () {
        const qs = new URLSearchParams(location.search);
        const token = qs.get('token');

        const resetBlock = document.getElementById('reset-password-form');
        const signinForm = document.getElementById('signin-form');
        const forgotForm = document.getElementById('forgot-password-form');

        const p1  = document.getElementById('new-password');
        const p2  = document.getElementById('confirm-password');
        const btn = document.getElementById('reset-submit');

        if (!resetBlock || !p1 || !p2 || !btn) return;

        // Show reset UI if token present
        if (token) {
            resetBlock.classList.remove('hidden');
            signinForm?.classList.add('hidden');
            forgotForm?.classList.add('hidden');
        }

        // Strength + match
        const bar   = document.getElementById('strength-bar');
        const label = document.getElementById('strength-text');
        const reqLen = document.querySelector('#req-length i');
        const reqUp  = document.querySelector('#req-uppercase i');
        const reqLo  = document.querySelector('#req-lowercase i');
        const reqNum = document.querySelector('#req-number i');
        const matchRow = document.getElementById('password-match');

        function update() {
            const v1 = p1.value, v2 = p2.value;
            const checks = [ v1.length>=8, /[A-Z]/.test(v1), /[a-z]/.test(v1), /\d/.test(v1) ];
            const score = checks.filter(Boolean).length;

            if (bar && label) {
                bar.className = 'strength-bar h-2 rounded-full';
                if (score <= 1) { bar.classList.add('strength-weak');  label.textContent='Weak'; }
                else if (score === 2) { bar.classList.add('strength-fair'); label.textContent='Fair'; }
                else if (score === 3) { bar.classList.add('strength-good'); label.textContent='Good'; }
                else { bar.classList.add('strength-strong'); label.textContent='Strong'; }
            }
            if (reqLen) reqLen.className = checks[0] ? 'ri-check-circle-line text-green-500' : 'ri-close-circle-line text-red-500';
            if (reqUp)  reqUp.className  = checks[1] ? 'ri-check-circle-line text-green-500' : 'ri-close-circle-line text-red-500';
            if (reqLo)  reqLo.className  = checks[2] ? 'ri-check-circle-line text-green-500' : 'ri-close-circle-line text-red-500';
            if (reqNum) reqNum.className = checks[3] ? 'ri-check-circle-line text-green-500' : 'ri-close-circle-line text-red-500';

            const match = v1.length>0 && v1===v2;
            matchRow?.classList.toggle('hidden', match);

            const ok = !!token && score===4 && match;
            btn.disabled = !ok;
            btn.classList.toggle('opacity-50', !ok);
            btn.classList.toggle('cursor-not-allowed', !ok);
        }
        ['input','change','keyup','blur'].forEach(ev => { p1.addEventListener(ev, update); p2.addEventListener(ev, update); });
        update();

        // Submit via temporary POST form so browser follows server redirect
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (btn.disabled || !token) return;

            btn.disabled = true;
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Saving...';

            const f = document.createElement('form');
            f.method = 'POST';
            f.action = '/matchymatchy/PHP/reset-password.php';

            const t  = document.createElement('input');
            t.type='hidden'; t.name='token'; t.value=token;

            const i1 = document.createElement('input');
            i1.type='hidden'; i1.name='p1'; i1.value=p1.value;

            const i2 = document.createElement('input');
            i2.type='hidden'; i2.name='p2'; i2.value=p2.value;

            f.appendChild(t); f.appendChild(i1); f.appendChild(i2);
            document.body.appendChild(f);
            f.submit();
        });
    });
</script>
</body>


</html>