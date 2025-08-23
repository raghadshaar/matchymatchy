(function () {
    if (window.__headerInit) return;
    window.__headerInit = true;

    const HEADER_PATH = '/matchymatchy/HTML/header.html';
    const MOUNT_SELECTOR = '#header, #header-slot';

    function getCart() {
        try { return JSON.parse(localStorage.getItem('cart') || '[]'); }
        catch { return []; }
    }
    function setCart(next) {
        localStorage.setItem('cart', JSON.stringify(next));
        window.dispatchEvent(new Event('cart:updated'));
    }
    function getCartCount() {
        return getCart().reduce((n, it) => n + (Number(it.quantity) || 0), 0);
    }
    function updateHeaderCartCount() {
        const el =
            document.querySelector('#header #cart-count') ||
            document.querySelector('#header-slot #cart-count') ||
            document.getElementById('cart-count') ||
            document.querySelector('.main-header .cart .badge');
        if (el) el.textContent = getCartCount();
    }

    // وظائف إدارة القوائم المنسدلة
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const backdrop = document.getElementById('backdrop');

        // إغلاق جميع القوائم أولاً
        closeAllDropdowns();

        // فتح القائمة المحددة
        if (dropdown) {
            dropdown.style.display = 'block';
            if (backdrop) backdrop.style.display = 'block';
        }
    }

    function closeAllDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown-panel');
        const backdrop = document.getElementById('backdrop');

        dropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
        });

        if (backdrop) backdrop.style.display = 'none';
    }

    function buildMenuFromCategories() {
        fetch('/matchymatchy/backend/categories_api.php?nav=1')
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (!data.success || !data.categories) {
                    throw new Error('Invalid data format from API');
                }

                const categories = data.categories;
                const parents = categories.filter(c => !c.parent_id && c.status === 'active');

                const navUl = document.querySelector('.nav-menu ul');
                const header = document.querySelector('.main-header');

                // تنظيف القائمة الحالية (مع الحفاظ على "Shop All")
                const shopAllItem = navUl.querySelector('li:last-child');
                if (shopAllItem && shopAllItem.querySelector('a[href*="all-products"]')) {
                    navUl.innerHTML = '';
                    navUl.appendChild(shopAllItem);
                }

                // تنظيف dropdowns السابقة
                const dropdownContainer = document.getElementById('category-dropdowns');
                if (dropdownContainer) dropdownContainer.innerHTML = '';

                parents.forEach(parent => {
                    const parentSlug = parent.slug;
                    const dropdownId = `${parentSlug}Dropdown`;

                    // عنصر في الشريط العلوي
                    const li = document.createElement('li');
                    li.className = 'menu-item';
                    li.setAttribute('onclick', `toggleDropdown('${dropdownId}')`);
                    li.textContent = parent.name;
                    navUl.insertBefore(li, navUl.lastElementChild);

                    // قائمة منسدلة
                    const dropdown = document.createElement('div');
                    dropdown.className = 'dropdown-panel';
                    dropdown.id = dropdownId;

                    // الحصول على الفئات الفرعية من مصفوفة children
                    const childLinks = (parent.children || [])
                        .filter(child => child.status === 'active')
                        .map(child => `<li><a href="category.html?slug=${child.slug}">${child.name}</a></li>`)
                        .join('');

                    dropdown.innerHTML = `
                        <div class="dropdown-header">
                            <strong>${parent.name}</strong>
                            <span class="close-btn" onclick="closeAllDropdowns()">&times;</span>
                        </div>
                        <ul>
                            <li><a href="category.html?slug=${parent.slug}">Shop All</a></li>
                            ${childLinks}
                        </ul>
                    `;

                    (document.getElementById('category-dropdowns') || header).appendChild(dropdown);
                });
            })
            .catch(err => {
                console.error('Error loading menu:', err);
                // في حالة الخطأ، إضافة فئات افتراضية للاختبار
                addDefaultCategories();
            });
    }

    // إضافة فئات افتراضية للاختبار
    function addDefaultCategories() {
        const navUl = document.querySelector('.nav-menu ul');
        const header = document.querySelector('.main-header');

        // تنظيف القائمة الحالية
        const shopAllItem = navUl.querySelector('li:last-child');
        if (shopAllItem && shopAllItem.querySelector('a[href*="all-products"]')) {
            navUl.innerHTML = '';
            navUl.appendChild(shopAllItem);
        }

        // تنظيف dropdowns السابقة
        const dropdownContainer = document.getElementById('category-dropdowns');
        if (dropdownContainer) dropdownContainer.innerHTML = '';

        // فئات افتراضية للاختبار
        const defaultCategories = [
            {
                id: 1,
                name: 'Baby',
                slug: 'baby',
                parent_id: null,
                status: 'active',
                children: [
                    { id: 7, name: 'Baby Girl', slug: 'baby-girl', parent_id: 1, status: 'active' },
                    { id: 8, name: 'Baby Boy', slug: 'baby-boy', parent_id: 1, status: 'active' }
                ]
            },
            {
                id: 2,
                name: 'Toddler',
                slug: 'toddler',
                parent_id: null,
                status: 'active',
                children: [
                    { id: 12, name: 'Toddler Girl', slug: 'toddler-girl', parent_id: 2, status: 'active' },
                    { id: 13, name: 'Toddler Boy', slug: 'toddler-boy', parent_id: 2, status: 'active' }
                ]
            }
        ];

        defaultCategories.forEach(parent => {
            const dropdownId = `${parent.slug}Dropdown`;

            // عنصر في الشريط العلوي
            const li = document.createElement('li');
            li.className = 'menu-item';
            li.setAttribute('onclick', `toggleDropdown('${dropdownId}')`);
            li.textContent = parent.name;
            navUl.insertBefore(li, navUl.lastElementChild);

            // قائمة منسدلة
            const dropdown = document.createElement('div');
            dropdown.className = 'dropdown-panel';
            dropdown.id = dropdownId;

            const childLinks = parent.children
                .map(child => `<li><a href="category.html?slug=${child.slug}">${child.name}</a></li>`)
                .join('');

            dropdown.innerHTML = `
                <div class="dropdown-header">
                    <strong>${parent.name}</strong>
                    <span class="close-btn" onclick="closeAllDropdowns()">&times;</span>
                </div>
                <ul>
                    <li><a href="category.html?slug=${parent.slug}">Shop All</a></li>
                    ${childLinks}
                </ul>
            `;

            (document.getElementById('category-dropdowns') || header).appendChild(dropdown);
        });
    }

    function mountHeader() {
        const mount = document.querySelector(MOUNT_SELECTOR);
        if (!mount) return;

        fetch(HEADER_PATH)
            .then((r) => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.text();
            })
            .then((html) => {
                mount.innerHTML = html;

                updateHeaderCartCount();
                buildMenuFromCategories();

                window.addEventListener('storage', (e) => {
                    if (e.key === 'cart') updateHeaderCartCount();
                });

                window.addEventListener('cart:updated', updateHeaderCartCount);

                const loginBtn = mount.querySelector('.icon.login');
                const cartBtn = mount.querySelector('.icon.cart');

                if (loginBtn && loginBtn.tagName !== 'A') {
                    loginBtn.style.cursor = 'pointer';
                    loginBtn.addEventListener('click', () => {
                        location.href = '/matchymatchy/HTML/login2.html';
                    });
                }
                if (cartBtn && cartBtn.tagName !== 'A') {
                    cartBtn.style.cursor = 'pointer';
                    cartBtn.addEventListener('click', () => {
                        location.href = '/HTML/cart.html';
                    });
                }

                // إضافة مستمع النقر للخلفية
                const backdrop = document.getElementById('backdrop');
                if (backdrop) {
                    backdrop.addEventListener('click', closeAllDropdowns);
                }
            })
            .catch((err) => console.error('Failed to load header:', err));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountHeader);
    } else {
        mountHeader();
    }

    // جعل الوظائف متاحة globally
    window.toggleDropdown = toggleDropdown;
    window.closeAllDropdowns = closeAllDropdowns;

    window.Cart = window.Cart || {
        getCart,
        setCart,
        getCartCount,
        updateHeaderCartCount,
    };
})();