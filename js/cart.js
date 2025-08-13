<!-- js/cart-badge.js -->
(function () {
    function getCartSafe() {
        try { return JSON.parse(localStorage.getItem('cart')) || []; }
        catch { return []; }
    }

    function calcCartCount() {
        return getCartSafe().reduce((sum, it) => sum + (Number(it.quantity) || 0), 0);
    }

    function findBadge() {
        return document.getElementById('global-cart-count')
            || document.querySelector('[data-cart-count]');
    }

    function syncCartBadge() {
        const badge = findBadge();
        if (badge) badge.textContent = calcCartCount();
    }

    // وفّري الدالة عالميًا لو احتجتي تستدعيها يدويًا بعد حقن الهيدر
    window.syncCartBadge = syncCartBadge;

    // أول تشغيل بعد جاهزية الـ DOM
    document.addEventListener('DOMContentLoaded', syncCartBadge);

    // لو الهيدر يتركّب لاحقًا عبر fetch/innerHTML، راقبي DOM لغاية ما تظهر الشارة ثم حدثيها
    const mo = new MutationObserver(() => {
        const badge = findBadge();
        if (badge) syncCartBadge();
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });

    // تحديث تلقائي لما تتغيّر السلة من تبويب آخر
    window.addEventListener('storage', (e) => {
        if (e.key === 'cart') syncCartBadge();
    });

    // حدث مخصص لتحديث العداد لما كودك يغيّر السلة محليًا
    window.addEventListener('cart:updated', syncCartBadge);
})();
