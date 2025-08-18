
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
        :where([class^="ri-"])::before { content: "\f3c2"; }
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
<?php if (!empty($_GET['notice'])): ?>
    <div class="mb-4 rounded-lg border px-4 py-3 flex items-center gap-2"
         style="border-color:#E4CFC3;background:#F9F8F3;color:red;">
        <i class="ri-information-line"></i>
        <span><?= htmlspecialchars($_GET['notice'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<body style="background-color: var(--baby);" class="min-h-screen pattern-bg">
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
                <div id="signin-form" class="space-y-6">
                    <div class="relative">
                        <input type="email" id="email" class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors peer" style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="email" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">Email Address</label>
                        <div class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-mail-line text-lg" style="color: var(--umber);"></i>
                        </div>
                    </div>
                    <div class="relative">
                        <input type="password" id="password" class="w-full px-4 py-3 pr-12 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors" style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
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
                    <button type="submit" class="w-full py-3 rounded-button font-semibold text-white transition-all hover:opacity-90 whitespace-nowrap" style="background-color: var(--teal);">
                Sign In
            </button>
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t" style="border-color: var(--pink);"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4" style="background-color: var(--white); color: var(--umber);">Or continue with</span>
                        </div>
                    </div>
                    <button type="button" id="google-signin" class="w-full py-3 rounded-button font-semibold text-white transition-all hover:opacity-90 whitespace-nowrap google-btn">
                        <div id="google-signin-content" class="flex items-center justify-center space-x-2">
                            <!-- داخل صفحة sign-in.php (نفس الصفحة اللي في الصورة) -->
                            <a href="/matchymatchy/google-login.php" class="btn-google">
                                Continue with Google
            </a>

                        </div>
                        <div id="google-signin-loading" class="hidden flex items-center justify-center space-x-2">
                            <i class="ri-loader-4-line animate-spin text-lg"></i>
                            <span>Authenticating...</span>
                        </div>
                    </button>
                    <div id="google-signin-error" class="hidden mt-2 text-sm text-red-500 flex items-center justify-center space-x-1">
                        <i class="ri-error-warning-line"></i>
                        <span></span>
                    </div>
                    <div class="text-center mt-6">
                        <p class="text-sm" style="color: var(--umber);">
                Don't have an account?
                            <a href="\matchymatchy\HTML\signup.html" data-readdy="true" class="font-semibold hover:underline" style="color: var(--teal);">Sign up</a>
                        </p>
                    </div>
                </div>
                <div id="forgot-password-form" class="space-y-6 hidden">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold mb-2" style="color: var(--jet);">Reset Password</h3>
                        <p class="text-sm" style="color: var(--umber);">Enter your email address and we'll send you a reset link</p>
                    </div>
                    <div class="relative">
                        <input type="email" id="reset-email" class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors" style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="reset-email" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">Email Address</label>
                        <div class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-mail-line text-lg" style="color: var(--umber);"></i>
                        </div>
                    </div>
                    <button type="submit" id="reset-link-button" class="w-full py-3 rounded-button font-semibold text-white transition-all hover:opacity-90 whitespace-nowrap flex items-center justify-center" style="background-color: var(--teal);">
                        <span id="reset-button-text">Send Reset Link</span>
                        <div id="reset-button-spinner" class="hidden ml-2">
                            <i class="ri-loader-4-line animate-spin"></i>
                        </div>
                    </button>
                    <button type="button" id="back-to-signin" class="w-full py-2 text-sm hover:underline" style="color: var(--umber);">
                Back to Sign In
            </button>
                </div>
                <div id="reset-password-form" class="space-y-6 hidden">
                    <div class="text-center mb-6">
                        <h3 class="text-xl font-bold mb-2" style="color: var(--jet);">Create New Password</h3>
                        <p class="text-sm" style="color: var(--umber);">Please enter your new password below</p>
                    </div>
                    <div class="relative">
                        <input type="password" id="new-password" class="w-full px-4 py-3 pr-12 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors" style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
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
                            <i class="ri-close-circle-line text-red-500"></i>
                            <span>At least 8 characters</span>
                        </p>
                        <p id="req-uppercase" class="flex items-center space-x-2">
                            <i class="ri-close-circle-line text-red-500"></i>
                            <span>One uppercase letter</span>
                        </p>
                        <p id="req-lowercase" class="flex items-center space-x-2">
                            <i class="ri-close-circle-line text-red-500"></i>
                            <span>One lowercase letter</span>
                        </p>
                        <p id="req-number" class="flex items-center space-x-2">
                            <i class="ri-close-circle-line text-red-500"></i>
                            <span>One number</span>
                        </p>
                    </div>
                    <div class="relative">
                        <input type="password" id="confirm-password" class="w-full px-4 py-3 pr-12 border-2 rounded-lg focus:outline-none focus:border-primary transition-colors" style="border-color: var(--pink); background-color: var(--baby);" placeholder=" " required>
                        <label for="confirm-password" class="floating-label absolute left-4 top-3 text-sm pointer-events-none" style="color: var(--umber);">Confirm Password</label>
                        <button type="button" id="toggle-confirm-password" class="absolute right-3 top-3 w-6 h-6 flex items-center justify-center">
                            <i class="ri-eye-line text-lg" style="color: var(--umber);"></i>
                        </button>
                    </div>
                    <div id="password-match" class="text-xs hidden">
                        <p class="flex items-center space-x-2 text-red-500">
                            <i class="ri-close-circle-line"></i>
                            <span>Passwords do not match</span>
                        </p>
                    </div>
                    <button type="submit" id="reset-submit" class="w-full py-3 rounded-button font-semibold text-white transition-all hover:opacity-90 whitespace-nowrap opacity-50 cursor-not-allowed" style="background-color: var(--teal);" disabled>
            Reset Password
            </button>
                </div>
                <div id="success-message" class="text-center space-y-4 hidden">
                    <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center" style="background-color: var(--teal);">
                        <i class="ri-check-line text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold" style="color: var(--jet);">Check Your Email</h3>
                    <p class="text-sm" style="color: var(--umber);">We've sent a password reset link to your email address. Please check your inbox and follow the instructions.</p>
                    <button type="button" id="back-to-signin-success" class="text-sm hover:underline" style="color: var(--teal);">Back to Sign In</button>
                </div>
            </div>
            <div class="text-center mt-6">
                <div class="flex items-center justify-center space-x-4 text-xs" style="color: var(--umber);">
                    <div class="flex items-center space-x-1">
                        <i class="ri-shield-check-line"></i>
                        <span>Secure Login</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <i class="ri-lock-line"></i>
                        <span>SSL Protected</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <i class="ri-user-heart-line"></i>
                        <span>Privacy First</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script id="form-navigation">
    document.addEventListener('DOMContentLoaded', function() {
        const signinForm = document.getElementById('signin-form');
        const forgotPasswordForm = document.getElementById('forgot-password-form');
        const resetPasswordForm = document.getElementById('reset-password-form');
        const successMessage = document.getElementById('success-message');
        const forgotPasswordBtn = document.getElementById('forgot-password-btn');
        const backToSigninBtn = document.getElementById('back-to-signin');
        const backToSigninSuccessBtn = document.getElementById('back-to-signin-success');
        function showForm(formToShow) {
            [signinForm, forgotPasswordForm, resetPasswordForm, successMessage].forEach(form => {
                form.classList.add('hidden');
            });
            formToShow.classList.remove('hidden');
        }
        forgotPasswordBtn.addEventListener('click', () => showForm(forgotPasswordForm));
        backToSigninBtn.addEventListener('click', () => showForm(signinForm));
        backToSigninSuccessBtn.addEventListener('click', () => showForm(signinForm));
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('reset') === 'true') {
            showForm(resetPasswordForm);
        }
    });
</script>
<script id="password-visibility">
    document.addEventListener('DOMContentLoaded', function() {
        function setupPasswordToggle(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            if (input && toggle) {
                toggle.addEventListener('click', function() {
                    const icon = toggle.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.className = 'ri-eye-off-line text-lg';
                    } else {
                        input.type = 'password';
                        icon.className = 'ri-eye-line text-lg';
                    }
                });
            }
        }
        setupPasswordToggle('password', 'toggle-password');
        setupPasswordToggle('new-password', 'toggle-new-password');
        setupPasswordToggle('confirm-password', 'toggle-confirm-password');
    });
</script>
<script id="floating-labels">
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
        inputs.forEach(input => {
            const container = input.parentElement;
            function updateLabel() {
                if (input.value.trim() !== '' || input === document.activeElement) {
                    container.classList.add('input-focused');
                } else {
                    container.classList.remove('input-focused');
                }
            }
            input.addEventListener('focus', updateLabel);
            input.addEventListener('blur', updateLabel);
            input.addEventListener('input', updateLabel);
            updateLabel();
        });
    });
</script>
<script id="password-strength">
    document.addEventListener('DOMContentLoaded', function() {
        const newPasswordInput = document.getElementById('new-password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        const resetSubmitBtn = document.getElementById('reset-submit');
        const passwordMatchDiv = document.getElementById('password-match');
        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            lowercase: document.getElementById('req-lowercase'),
            number: document.getElementById('req-number')
        };
        function updateRequirement(element, met) {
            const icon = element.querySelector('i');
            if (met) {
                icon.className = 'ri-check-circle-line text-green-500';
                element.style.color = 'var(--teal)';
            } else {
                icon.className = 'ri-close-circle-line text-red-500';
                element.style.color = 'var(--umber)';
            }
        }
        function checkPasswordStrength(password) {
            const checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };
            Object.keys(checks).forEach(key => {
                updateRequirement(requirements[key], checks[key]);
            });
            const score = Object.values(checks).filter(Boolean).length;
            strengthBar.className = 'strength-bar h-2 rounded-full';
            switch(score) {
                case 0:
                case 1:
                    strengthBar.classList.add('strength-weak');
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#ef4444';
                    break;
                case 2:
                    strengthBar.classList.add('strength-fair');
                    strengthText.textContent = 'Fair';
                    strengthText.style.color = '#f59e0b';
                    break;
                case 3:
                    strengthBar.classList.add('strength-good');
                    strengthText.textContent = 'Good';
                    strengthText.style.color = '#10b981';
                    break;
                case 4:
                    strengthBar.classList.add('strength-strong');
                    strengthText.textContent = 'Strong';
                    strengthText.style.color = 'var(--teal)';
                    break;
            }
            return score === 4;
        }
        function checkPasswordMatch() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            if (confirmPassword && password !== confirmPassword) {
                passwordMatchDiv.classList.remove('hidden');
                return false;
            } else {
                passwordMatchDiv.classList.add('hidden');
                return true;
            }
        }
        function updateSubmitButton() {
            const isStrongPassword = checkPasswordStrength(newPasswordInput.value);
            const passwordsMatch = checkPasswordMatch();
            if (isStrongPassword && passwordsMatch && confirmPasswordInput.value) {
                resetSubmitBtn.disabled = false;
                resetSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                resetSubmitBtn.disabled = true;
                resetSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', updateSubmitButton);
        }
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', updateSubmitButton);
        }
    });
</script>
<script id="form-submissions">
    document.addEventListener('DOMContentLoaded', function() {
        const signinForm = document.getElementById('signin-form');
        const forgotPasswordForm = document.getElementById('forgot-password-form');
        const resetPasswordForm = document.getElementById('reset-password-form');
        const emailInput = document.getElementById('email');
        const rememberMeCheckbox = document.getElementById('remember-me');
        function loadSavedEmail() {
            const savedEmail = localStorage.getItem('rememberedEmail');
            if (savedEmail) {
                emailInput.value = savedEmail;
                rememberMeCheckbox.checked = true;
                emailInput.parentElement.classList.add('input-focused');
            }
        }
        loadSavedEmail();
        const successMessage = document.getElementById('success-message');
        const googleSigninBtn = document.getElementById('google-signin');
        const googleSigninContent = document.getElementById('google-signin-content');
        const googleSigninLoading = document.getElementById('google-signin-loading');
        const googleSigninError = document.getElementById('google-signin-error');
        function showForm(formToShow) {
            [signinForm, forgotPasswordForm, resetPasswordForm, successMessage].forEach(form => {
                form.classList.add('hidden');
            });
            formToShow.classList.remove('hidden');
        }
        signinForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            if (rememberMeCheckbox.checked) {
                localStorage.setItem('rememberedEmail', email);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
            console.log('Sign in attempt:', { email, password: '***' });
            const successMessage = document.createElement('div');
            successMessage.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2';
            successMessage.innerHTML = `
  <i class="ri-checkbox-circle-line"></i>
  <span>Successfully signed in!</span>
`;
            document.body.appendChild(successMessage);
            setTimeout(() => successMessage.remove(), 3000);
        });
        forgotPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = document.getElementById('reset-email');
            const resetButton = document.getElementById('reset-link-button');
            const buttonText = document.getElementById('reset-button-text');
            const buttonSpinner = document.getElementById('reset-button-spinner');
            if (!emailInput.value) {
                emailInput.classList.add('border-red-500');
                emailInput.parentElement.insertAdjacentHTML('afterend',
                    '<div class="text-red-500 text-xs mt-1 flex items-center"><i class="ri-error-warning-line mr-1"></i>Please enter your email address</div>');
                return;
            }
            if (!emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                emailInput.classList.add('border-red-500');
                emailInput.parentElement.insertAdjacentHTML('afterend',
                    '<div class="text-red-500 text-xs mt-1 flex items-center"><i class="ri-error-warning-line mr-1"></i>Please enter a valid email address</div>');
                return;
            }
            const errorMessage = emailInput.parentElement.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('text-red-500')) {
                errorMessage.remove();
            }
            emailInput.classList.remove('border-red-500');
            resetButton.disabled = true;
            resetButton.classList.add('opacity-70');
            buttonText.textContent = 'Sending...';
            buttonSpinner.classList.remove('hidden');
            setTimeout(() => {
                if (Math.random() > 0.5) {
                    resetButton.disabled = false;
                    resetButton.classList.remove('opacity-70');
                    buttonText.textContent = 'Send Reset Link';
                    buttonSpinner.classList.add('hidden');
                    showForm(successMessage);
                } else {
                    resetButton.disabled = false;
                    resetButton.classList.remove('opacity-70');
                    buttonText.textContent = 'Send Reset Link';
                    buttonSpinner.classList.add('hidden');
                    emailInput.classList.add('border-red-500');
                    emailInput.parentElement.insertAdjacentHTML('afterend',
                        '<div class="text-red-500 text-xs mt-1 flex items-center"><i class="ri-error-warning-line mr-1"></i>Email address not found</div>');
                }
            }, 2000);
        });
        resetPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            if (newPassword === confirmPassword) {
                console.log('Password reset successful');
                alert('Password has been reset successfully!');
                showForm(signinForm);
            }
        });
        function initGoogleAuth() {
            return new Promise((resolve, reject) => {
                setTimeout(() => {
                    if (Math.random() > 0.3) {
                        resolve({ email: 'user@example.com', name: 'Demo User' });
                    } else {
                        reject(new Error('Failed to authenticate with Google'));
                    }
                }, 2000);
            });
        }
        function showGoogleError(message) {
            googleSigninError.querySelector('span').textContent = message;
            googleSigninError.classList.remove('hidden');
            setTimeout(() => {
                googleSigninError.classList.add('hidden');
            }, 5000);
        }
        function setGoogleButtonState(isLoading) {
            googleSigninBtn.disabled = isLoading;
            if (isLoading) {
                googleSigninContent.classList.add('hidden');
                googleSigninLoading.classList.remove('hidden');
            } else {
                googleSigninContent.classList.remove('hidden');
                googleSigninLoading.classList.add('hidden');
            }
        }
        googleSigninBtn.addEventListener('click', async function() {
            try {
                setGoogleButtonState(true);
                googleSigninError.classList.add('hidden');
                const userData = await initGoogleAuth();
                console.log('Google Sign-In successful:', userData);
                window.location.href = '/dashboard';
            } catch (error) {
                console.error('Google Sign-In failed:', error);
                showGoogleError(error.message || 'Authentication failed. Please try again.');
                setGoogleButtonState(false);
            }
        });
    });
</script>
</body>
</html>