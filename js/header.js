// /js/header.js
(function () {
    // امنع التهيئة المكررة لو الملف اتضمّن مرتين
    if (window.__headerInit) return;
    window.__headerInit = true;

    // غيّر المسار حسب مشروعك (يفضل مطلقًا من الجذر)
    const HEADER_PATH = '/matchymatchy/HTML/header.html'; // أو 'header.html' لو بنفس المستوى
    const MOUNT_SELECTOR = '#header, #header-slot';

    // --- مخزن السلة البسيط + تحديث البادج ---
    function getCart() {
        try { return JSON.parse(localStorage.getItem('cart') || '[]'); }
        catch { return []; }
    }
    function setCart(next) {
        localStorage.setItem('cart', JSON.stringify(next));
        window.dispatchEvent(new Event('cart:updated')); // يحدث البادج فورًا في نفس الصفحة
    }
    function getCartCount() {
        return getCart().reduce((n, it) => n + (Number(it.quantity) || 0), 0);
    }
    function updateHeaderCartCount() {
        // يشتغل سواء الهيدر داخل #header أو #header-slot أو مباشرة
        const el =
            document.querySelector('#header #cart-count') ||
            document.querySelector('#header-slot #cart-count') ||
            document.getElementById('cart-count') ||
            document.querySelector('.main-header .cart .badge');
        if (el) el.textContent = getCartCount();
    }

    // --- تحميل الهيدر وتهيئة المستمعين ---
    function mountHeader() {
        const mount =
            document.querySelector('#header') ||
            document.querySelector('#header-slot');
        if (!mount) return; // الصفحة ما فيها مكان للهيدر

        fetch(HEADER_PATH)
            .then((r) => r.text())
            .then((html) => {
                mount.innerHTML = html;

                // تحديث أولي
                updateHeaderCartCount();

                // تزامن بين التبويبات/الصفحات
                window.addEventListener('storage', (e) => {
                    if (e.key === 'cart') updateHeaderCartCount();
                });

                // تزامن داخل نفس الصفحة
                window.addEventListener('cart:updated', updateHeaderCartCount);

                // (اختياري) اجعل الأيقونات تنقل لو مش <a>
                const loginBtn = mount.querySelector('.icon.login');
                const cartBtn = mount.querySelector('.icon.cart');

                if (loginBtn && loginBtn.tagName !== 'A') {
                    loginBtn.style.cursor = 'pointer';
                    loginBtn.addEventListener('click', () => {
                        location.href = '/matchymatchy/HTML/login2.html'; // عدل المسار
                    });
                }
                if (cartBtn && cartBtn.tagName !== 'A') {
                    cartBtn.style.cursor = 'pointer';
                    cartBtn.addEventListener('click', () => {
                        location.href = '/HTML/cart.html'; // عدل المسار
                    });
                }
            })
            .catch((err) => console.error('Failed to load header:', err));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountHeader);
    } else {
        mountHeader();
    }

    // عرّض API خفيفة لو احتجتها بصفحات المنتجات
    window.Cart = window.Cart || {
        getCart,
        setCart,
        getCartCount,
        updateHeaderCartCount,
    };
})();
