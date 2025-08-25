<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/auth_db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'pdo_init_failed']);
exit;
}
$me = require_admin_page_db($pdo, '/matchymatchy/sign-in.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Matchy Matchy — Admin • Products</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/sidebar.css" />
    <link rel="stylesheet" href="../styles/products.css" />
    <link rel="stylesheet" href="../styles/topbarAdmin.css" />

</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <div id="sidebar-container"></div>

    <!-- Main -->
    <main class="main">
        <!-- Topbar -->
        <div data-include="topBarAdmin.html"></div>

        <!-- Page header -->
        <section class="content"> </section>
        <div class="page-head">
            <div class="title-wrap">
                <h1>Products</h1>
                <p>Manage your Matchy Matchy catalog—kids & baby fashion, toys, essentials, and family matching sets. Prices shown in ILS (₪).</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="searchInput" type="search" placeholder="Search products by name or SKU…">
            </div>
            <div class="field">
                <i class="fa-solid fa-layer-group"></i>
                <select id="collectionFilter" aria-label="Filter by collection">
                    <option value="">All Collections</option>
                </select>
            </div>
            <div class="field">
                <i class="fa-solid fa-signal"></i>
                <select id="statusFilter" aria-label="Filter by status">
                    <option value="">All Status</option>
                    <option value="In Stock">In Stock</option>
                    <option value="Low Stock">Low Stock</option>
                    <option value="Out of Stock">Out of Stock</option>
                    <option value="Draft">Draft</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>
            <button class="btn btn-ghost" id="clearFilters"><i class="fa-solid fa-rotate-left"></i> Clear</button>
            <div style="flex:1"></div>
            <button class="btn btn-teal" id="addProductBtn"><i class="fa-solid fa-plus"></i> Add Product</button>
        </div>

        <!-- Table -->
        <section class="card">
            <!-- Product Grid -->
            <div id="productGrid" class="product-grid"></div>

            <!-- Empty State -->
            <div id="emptyState" class="empty" style="display:none">
                <div class="emoji">🧸</div>
                <h3 style="margin:0 0 6px;color:var(--umber)">No products yet</h3>
                <p style="margin:0 0 14px">Add your first Matchy Matchy item and start building your family-friendly catalog.</p>
                <button class="btn btn-teal" id="emptyAddBtn"><i class="fa-solid fa-plus"></i> Add Product</button>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="empty">
                <div class="emoji">⏳</div>
                <h3 style="margin:0 0 6px;color:var(--umber)">Loading products...</h3>
                <p style="margin:0 0 14px">Please wait while we load your products.</p>
            </div>

            <!-- Error State -->
            <div id="errorState" class="empty" style="display:none">
                <div class="emoji">❌</div>
                <h3 style="margin:0 0 6px;color:var(--umber)">Failed to load products</h3>
                <p style="margin:0 0 14px" id="errorMessage">There was an error loading your products. Please try again.</p>
                <button class="btn btn-teal" id="retryButton"><i class="fa-solid fa-refresh"></i> Try Again</button>
            </div>

            <!-- Pagination -->
            <div class="pager">
                <div class="pager-info" id="pagerInfo"></div>
                <button class="page-btn" id="prevPage"><i class="fa-solid fa-chevron-left"></i></button>
                <div id="pageNums" style="display:flex;gap:6px"></div>
                <button class="page-btn" id="nextPage"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>

    </main>
</div>

<!-- Drawer -->
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<div class="drawer" id="drawer">
    <h3 id="drawerTitle">Add Product</h3>
    <div class="form-grid">
        <div class="form-item">
            <label for="pName">Name</label>
            <input class="input" id="pName" placeholder="e.g., Family Matching Pajama Set"/>
        </div>
        <div class="form-item">
            <label for="pSKU">SKU</label>
            <input class="input" id="pSKU" placeholder="e.g., PJ-FAM-001"/>
        </div>
        <div class="form-item">
            <label for="pPrice">Price (₪)</label>
            <input class="input" id="pPrice" type="number" step="0.01" min="0" placeholder="e.g., 149.90"/>
        </div>
        <div class="form-item">
            <label for="pStock">Stock</label>
            <input class="input" id="pStock" type="number" min="0" placeholder="e.g., 100"/>
        </div>
        <div class="form-item">
            <label for="pCollection">Collection</label>
            <select class="select" id="pCollection">
                <option>Baby</option>
                <option>Toddler</option>
                <option>Kids</option>
                <option>Family Matching</option>
                <option>Toys</option>
                <option>Essentials</option>
                <option>Accessories</option>
                <option>Brands</option>
                <option>Deals</option>
            </select>
        </div>
        <div class="form-item">
            <label for="pChildCat">Child Category</label>
            <select class="select" id="pChildCat">
                <option value="">— Select child —</option>
            </select>
        </div>
        <div class="form-item">
            <label for="pParentCat">Parent</label>
            <input class="input" id="pParentCat" placeholder="Auto" disabled />
        </div>

        <div class="form-item">
            <label for="pStatus">Status</label>
            <select class="select" id="pStatus">
                <option>Auto (by stock)</option>
                <option>In Stock</option>
                <option>Low Stock</option>
                <option>Out of Stock</option>
                <option>Draft</option>
                <option>Archived</option>
            </select>
        </div>
        <div class="form-item" style="grid-column:1/-1">
            <label for="pDesc">Description</label>
            <textarea class="textarea" id="pDesc" placeholder="Short description…"></textarea>
        </div>
        <div class="form-item">
            <label for="pType">Product Type</label>
            <select class="select" id="pType">
                <option value="">— Select type —</option>
                <option value="Blouse">Blouse</option>
                <option value="Pajama">Pajama</option>
                <option value="Pants">Pants</option>
                <option value="Skirt">Skirt</option>
                <option value="Dress">Dress</option>
                <option value="T-shirt">T-shirt</option>
                <option value="Set">Set</option>
            </select>
        </div>
        <div class="form-item">
            <label>Fabrics</label>
            <div id="fabricChips" class="chip-multi"></div>
        </div>
        <div class="form-item">
            <label>Colors</label>
            <div id="colorChips" class="chip-multi"></div>
        </div>



        <div class="form-item">
            <label>Sizes</label>
            <div class="size-chips" id="sizeChips"></div>
            <input class="input" id="customSize" placeholder="Add custom size and press Enter" style="margin-top:8px"/>
        </div>
        <div class="form-item">
            <label>Product Image</label>

            <!-- Dropzone -->
            <div id="dropzone" style="
      border:2px dashed var(--teal); border-radius:12px; padding:14px; text-align:center;
      background:#F9F8F3; cursor:pointer;">
                <div style="font-size:14px; color:var(--umber);">
                    <strong>Drop image here</strong> or click to choose
                </div>
                <div id="dzPreview" style="margin-top:8px; display:none;">
                    <img id="dzImg" src="" alt="" style="max-width:100%; border-radius:8px"/>
                </div>
            </div>

            <input type="file" id="fileInput" accept="image/*" style="display:none"/>

            <!-- Keep the URL field (hidden) so your existing code keeps working -->
            <input class="input" id="pImage" placeholder="https://…" style="margin-top:8px" />
            <small style="color:var(--umber)">If you already have a URL, paste it; otherwise upload.</small>
        </div>
        <!-- Gallery (extra images) -->
        <div class="form-item" style="grid-column:1/-1">
            <label>Gallery Images</label>

            <div id="galleryGrid" class="gallery-grid"></div>

            <div class="gallery-actions" style="display:flex;gap:8px;margin-top:8px">
                <button type="button" class="btn btn-ghost" id="addGalleryBtn">
                    <i class="fa-solid fa-plus"></i> Add from device
                </button>
                <button type="button" class="btn btn-ghost" id="addGalleryUrlBtn">
                    <i class="fa-solid fa-link"></i> Add from URL
                </button>
                <input type="file" id="galleryFileInput" accept="image/*" multiple style="display:none"/>
            </div>

            <small style="color:var(--umber)">Drag to reorder. Each image can have alt text.</small>
        </div>

    </div>
    <div class="drawer-actions">
        <button class="btn btn-ghost" id="cancelDrawer">Cancel</button>
        <button class="btn btn-teal" id="saveProduct">Save</button>
    </div>
</div>

<!-- Add Color Dialog -->
<div id="addColorDialog" class="modal">
    <div class="modal-content">
        <h3>Add New Color</h3>
        <label>
            Color Name:
            <input type="text" id="newColorName" placeholder="e.g. Lavender"/>
        </label>
        <label>
            Color Picker:
            <input type="color" id="newColorHex" value="#DDA0DD"/>
        </label>
        <div class="modal-actions">
            <button class="btn btn-teal" onclick="submitNewColor()">Add</button>
            <button class="btn btn-ghost" onclick="closeAddColorDialog()">Cancel</button>
        </div>
    </div>
</div>
<script>
    const PAGE_SIZE = 8;
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
    const currencyILS = (n) => `₪${Number(n).toFixed(2)}`;
    let gallery = []; // [{url, alt, sort}]
    let selectedFabrics = new Set();
    let selectedColors = new Set();


    // حالات التحميل والعرض
    const showLoading = () => {
        $('#loadingState').style.display = 'block';
        $('#emptyState').style.display = 'none';
        $('#errorState').style.display = 'none';
        $('#productGrid').innerHTML = '';
    };

    const showError = (message) => {
        $('#loadingState').style.display = 'none';
        $('#emptyState').style.display = 'none';
        $('#errorState').style.display = 'block';
        $('#errorMessage').textContent = message || 'There was an error loading your products. Please try again.';
    };

    const showContent = () => {
        $('#loadingState').style.display = 'none';
        $('#errorState').style.display = 'none';
    };

    /* === Load Sidebar === */
    document.addEventListener('DOMContentLoaded', () => {
        fetch('sidebar.html').then(r => r.text()).then(html => {
            const el = $('#sidebar-container');
            el.innerHTML = html;
            $$('a', el).forEach(a => {
                if (a.textContent.trim().toLowerCase() === 'products') a.classList.add('active');
            });
        }).catch(err => {
            console.error('Error loading sidebar:', err);
        });

        // تحميل المنتجات عند بدء التحميل
        loadProducts();
        loadCollections();
        loadCategoryTreeForDrawer();

    });

    /* === حالة التطبيق === */
    let state = {
        q: '',
        collection: '',
        status: '',
        page: 1,
        products: [],
        total: 0,
        pages: 1
    };

    /* === تحميل المنتجات === */
    async function loadProducts() {
        showLoading();

        const params = new URLSearchParams({
            page: state.page,
            limit: PAGE_SIZE,
            search: state.q,
            collection: state.collection,
            status: state.status
        });

        try {
            const response = await fetch(`../backend/products_api.php?${params}`);

            // التحقق مما إذا كان الرد هو JSON صالح
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Server returned ${response.status}: ${response.statusText}. Response: ${text.substring(0, 100)}...`);
            }

            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            state.products = data.products || [];
            state.total = data.total || 0;
            state.pages = data.pages || 1;

            showContent();
            render();
        } catch (error) {
            console.error('Error loading products:', error);
            showError(error.message);
        }
    }

    /* === تحميل الفئات === */
    async function loadCollections() {
        try {
            const response = await fetch('../backend/collections_api.php');  // نفس الرابط
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server did not return JSON');
            }
            if (!response.ok) throw new Error(`Server returned ${response.status}: ${response.statusText}`);

            const j = await response.json();

            // لاحظي: الآن الجواب كائن فيه data = مصفوفة آباء
            const parents = Array.isArray(j.data) ? j.data : [];

            // إن بدك أسماء بس:
            const names = parents.map(p => p.name);

            $('#collectionFilter').innerHTML =
                '<option value="">All Collections</option>' +
                names.map(n => `<option value="${n}">${n}</option>`).join('');
        } catch (error) {
            console.error('Error loading collections:', error);
        }
    }


    /* === عرض المنتجات === */
    function statusChipClass(st) {
        if (st === 'In Stock') return 'green';
        if (st === 'Low Stock') return 'amber';
        if (st === 'Out of Stock') return 'red';
        return 'amber';
    }

    function render() {
        $('#productGrid').innerHTML = state.products.map(p => `
            <div class="product-card">
                <div class="product-media">
                    <img src="${p.image_main_url || '../images/placeholder.jpg'}" alt="${p.name}" onerror="this.src='../images/placeholder.jpg'">
                </div>

                <div class="product-info">
                    <h4 class="product-title">${p.name}</h4>
                    <div class="product-sku">SKU: ${p.sku || '-'}</div>

                    <div class="product-price">${currencyILS(p.price || 0)}</div>

                    <div class="product-meta">
                        <div>Collection: ${p.collections || '-'}</div>
                        <div>Stock: ${p.stock ?? 0}</div>
                        <div>Type: ${p.type || '-'}</div>
                        <div>Fabrics: ${p.fabrics?.join(', ') || '-'}</div>
                         <div>Colors: ${p.colors?.join(', ') || '-'}</div>
                    </div>

                    <div class="product-status">
                        <span class="chip ${statusChipClass(p.status)}">${p.status}</span>
                    </div>
                </div>

                <div class="card-actions">
                    <button class="btn btn-ghost" data-edit="${p.id}">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-ghost" data-del="${p.id}">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `).join('');

        $('#emptyState').style.display = state.products.length ? 'none' : 'block';
        $('#pagerInfo').textContent = state.products.length
            ? `Showing ${Math.min((state.page-1)*PAGE_SIZE+1, state.total)}–${Math.min(state.page*PAGE_SIZE,state.total)} of ${state.total} products`
            : 'No results';

        $('#pageNums').innerHTML = '';
        for(let i = 1; i <= state.pages; i++) {
            const b = document.createElement('button');
            b.className = 'page-btn' + (i === state.page ? ' active' : '');
            b.textContent = i;
            b.onclick = () => { state.page = i; loadProducts(); };
            $('#pageNums').appendChild(b);
        }
        $('#prevPage').disabled = state.page <= 1;
        $('#nextPage').disabled = state.page >= state.pages;

        $$('[data-edit]').forEach(btn => btn.onclick = () => openDrawerForEdit(+btn.dataset.edit));
        $$('[data-del]').forEach(btn => btn.onclick = () => deleteProduct(+btn.dataset.del));
    }

    /* === البحث والتصفية === */
    function bindSearchAndFilters() {
        $('#searchInput').oninput = e => { state.q = e.target.value; state.page = 1; loadProducts(); };
        $('#collectionFilter').onchange = e => { state.collection = e.target.value; state.page = 1; loadProducts(); };
        $('#statusFilter').onchange = e => { state.status = e.target.value; state.page = 1; loadProducts(); };
        $('#clearFilters').onclick = () => {
            state = { q: '', collection: '', status: '', page: 1, products: [], total: 0, pages: 1 };
            $('#searchInput').value = '';
            $('#collectionFilter').value = '';
            $('#statusFilter').value = '';
            loadProducts();
        };
        $('#retryButton').onclick = () => loadProducts();
    }
    bindSearchAndFilters();

    /* === الدرج والنموذج === */
    const drawer = $('#drawer');
    const backdrop = $('#drawerBackdrop');
    const drawerTitle = $('#drawerTitle');
    const defaultSizes = ['NB','0-3M','3-6M','6-9M','9-12M','12-18M','18-24M','2T','3T','4T','5-6Y','7-8Y','Mom S','Mom M','Mom L','Dad M','Dad L','One Size'];
    const sizeChips = $('#sizeChips');
    let selectedSizes = new Set();
    const formEls = {
        name: $('#pName'), sku: $('#pSKU'), price: $('#pPrice'), stock: $('#pStock'),
        collection: $('#pCollection'), status: $('#pStatus'), desc: $('#pDesc'), image: $('#pImage')
    };
    let editingId = null;

    function renderSizeChips() {
        sizeChips.innerHTML = '';
        defaultSizes.forEach(s => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'chip-toggle' + (selectedSizes.has(s) ? ' active' : '');
            b.textContent = s;
            b.onclick = () => { selectedSizes.has(s) ? selectedSizes.delete(s) : selectedSizes.add(s); renderSizeChips(); };
            sizeChips.appendChild(b);
        });
    }

    $('#customSize').onkeydown = e => {
        if (e.key === 'Enter' && e.target.value.trim()) {
            selectedSizes.add(e.target.value.trim());
            e.target.value = '';
            renderSizeChips();
        }
    };

    function resetForm() {
        editingId = null;
        drawerTitle.textContent = 'Add Product';

        // فضّي كل الفيلدز
        Object.values(formEls).forEach(el => el.value = '');
        $('#pStatus').value = 'Auto (by stock)';
        $('#pCollection').value = '';          // تأكيد صريح
        $('#customSize').value = '';          // امسحي المقاس المخصص

        // المقاسات
        selectedSizes = new Set(['One Size']);
        renderSizeChips();

        const dzPrev = document.getElementById('dzPreview');
        const dzImg  = document.getElementById('dzImg');
        const dz     = document.getElementById('dropzone');
        if (dzPrev) dzPrev.style.display = 'none';
        if (dzImg)  dzImg.src = '';
        if (dz)     dz.style.background = '#F9F8F3';
        const fileInput = document.getElementById('fileInput');
        if (fileInput) fileInput.value = '';

        // المعرض (الصور الإضافية)
        gallery = [];
        const gg = document.getElementById('galleryGrid');
        if (gg) gg.innerHTML = '';

        // (اختياري) مرّري الدرج لأعلى
        const drw = document.getElementById('drawer');
        if (drw) drw.scrollTop = 0;


        selectedFabrics = new Set();
        selectedColors = new Set();
        renderFabricChips();
        renderColorChips();

    }


    function openDrawer() {
        drawer.classList.add('open');
        backdrop.classList.add('show');
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        backdrop.classList.remove('show');
        resetForm();
    }

    function openDrawerForAdd() {
        resetForm();
        openDrawer();
    }

    async function openDrawerForEdit(id) {
        try {
            const response = await fetch(`../backend/products_api.php?id=${id}`);

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server did not return JSON');
            }

            const product = await response.json();

            editingId = id;
            drawerTitle.textContent = 'Edit Product';

            formEls.name.value = product.name || '';
            formEls.sku.value = product.sku || '';
            formEls.price.value = product.price || 0;
            formEls.stock.value = product.stock || 0;
            formEls.collection.value = product.collections || '';
            formEls.status.value = product.status || 'Auto (by stock)';
            formEls.desc.value = product.description || '';
            formEls.image.value = product.image_main_url || '';

            selectedSizes = new Set(product.sizes || []);

            renderSizeChips();
            gallery = (product.images || []).map((im, i) => ({
                url: im.image_url || im.url,
                alt: im.alt_text || im.alt || '',
                sort: (typeof im.sort_order === 'number') ? im.sort_order : i
            }));
            gallery.sort((a,b)=>a.sort-b.sort);
            renderGallery();
            document.getElementById('pType').value = product.type || '';
            selectedFabrics = new Set(product.fabrics || []);
            selectedColors  = new Set(product.colors || []);
            renderFabricChips();
            renderColorChips();


            openDrawer();
        } catch (error) {
            console.error('Error loading product:', error);
            alert('Failed to load product details.');
        }
    }

    function validateForm() {
        const name = formEls.name.value.trim();
        const sku = formEls.sku.value.trim();
        const price = Number(formEls.price.value || 0);

        if (!name) { alert('Please enter product name'); return false; }
        if (!sku) { alert('Please enter product SKU'); return false; }
        if (price < 0) { alert('Price cannot be negative'); return false; }

        return true;
    }

    async function saveProduct() {
        if (!validateForm()) return;

        const payload = {
            id: editingId,
            name: formEls.name.value.trim(),
            sku: formEls.sku.value.trim(),
            price: Number(formEls.price.value || 0),
            stock: Math.max(0, Number(formEls.stock.value || 0)),
            category_id: Number(document.getElementById('pChildCat').value) || 0, // <<<<<<
            collection: formEls.collection.value,
            status: formEls.status.value,
            description: formEls.desc.value.trim(),
            image: formEls.image.value.trim(),
            sizes: Array.from(selectedSizes),
            gallery: gallery,
            type: document.getElementById('pType').value,
            fabrics: Array.from(selectedFabrics),
            colors: Array.from(selectedColors)

        };

        try {
            const response = await fetch('../backend/products_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            // التحقق من أن الرد هو JSON صالح
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Server returned ${response.status}: ${response.statusText}. Response: ${text.substring(0, 100)}...`);
            }

            const result = await response.json();

            if (result.success) {
                closeDrawer();
                loadProducts();
                loadCollections();
            } else {
                alert('Failed to save product: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving product:', error);
            alert('Failed to save product. Please try again.');
        }
    }

    async function deleteProduct(id) {
        const product = state.products.find(p => p.id === id);
        if (!product || !confirm(`Delete "${product.name}"?`)) return;

        try {
            const response = await fetch(`../backend/products_api.php?id=${id}`, {
                method: 'DELETE'
            });

            // التحقق من أن الرد هو JSON صالح
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Server returned ${response.status}: ${response.statusText}. Response: ${text.substring(0, 100)}...`);
            }

            const result = await response.json();

            if (result.success) {
                loadProducts();
                loadCollections();
            } else {
                alert('Failed to delete product: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error deleting product:', error);
            alert('Failed to delete product. Please try again.');
        }
    }

    $('#saveProduct').onclick = saveProduct;
    $('#addProductBtn').onclick = openDrawerForAdd;
    $('#emptyAddBtn').onclick = openDrawerForAdd;
    backdrop.onclick = closeDrawer;
    $('#cancelDrawer').onclick = closeDrawer;
</script>
<script>
    (function setupUpload(){
        const dz   = document.getElementById('dropzone');
        const fi   = document.getElementById('fileInput');
        const urlI = document.getElementById('pImage');
        const prev = document.getElementById('dzPreview');
        const img  = document.getElementById('dzImg');

        const pick = ()=> fi.click();
        const stop = e => { e.preventDefault(); e.stopPropagation(); };

        ['click'].forEach(ev => dz.addEventListener(ev, pick));
        ['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e => {
            stop(e); dz.style.background = '#E4CFC333';
        }));
        ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => {
            stop(e); dz.style.background = '#F9F8F3';
        }));

        dz.addEventListener('drop', e => {
            const f = e.dataTransfer.files?.[0]; if (f) upload(f);
        });
        fi.addEventListener('change', e => {
            const f = e.target.files?.[0]; if (f) upload(f);
        });

        async function upload(file){
            const fd = new FormData();
            fd.append('file', file);
            try{
                const res = await fetch('../backend/upload_image.php', { method:'POST', body:fd });
                const j   = await res.json();
                if (!j.ok) throw new Error(j.error || 'Upload failed');
                urlI.value = j.url;               // save URL for your existing payload
                img.src = j.url; prev.style.display='block';
            }catch(err){
                alert(err.message);
            }
        }
    })();
</script>
<script>
    // ===== Gallery (extra images) =====
    function renderGallery(){
        const wrap = document.getElementById('galleryGrid');
        wrap.innerHTML = '';
        gallery.forEach((g, idx) => {
            const item = document.createElement('div');
            item.className = 'gallery-item';
            item.draggable = true;
            item.innerHTML = `
        <button class="del" title="Remove">&times;</button>
        <span class="drag"><i class="fa-solid fa-grip-lines"></i></span>
        <img src="${g.url}" onerror="this.src='../images/placeholder.jpg'"/>
        <button class="main" title="Set as main">Set main</button>
        <div class="tools">
          <input type="text" placeholder="Alt text…" value="${g.alt || ''}">
        </div>
      `;
            wrap.appendChild(item);

            // حذف
            item.querySelector('.del').onclick = () => { gallery.splice(idx,1); renumber(); renderGallery(); };

            // Alt
            item.querySelector('input').oninput = (e)=>{ gallery[idx].alt = e.target.value; };

            // Set main -> ينسخ للـ pImage
            item.querySelector('.main').onclick = ()=>{
                document.getElementById('pImage').value = g.url;
                const dzImg = document.getElementById('dzImg');
                const dzPrev = document.getElementById('dzPreview');
                if (dzImg && dzPrev){ dzImg.src = g.url; dzPrev.style.display='block'; }
            };

            // سحب لإعادة الترتيب
            item.addEventListener('dragstart', e => { e.dataTransfer.setData('text/plain', String(idx)); });
            item.addEventListener('dragover', e => e.preventDefault());
            item.addEventListener('drop', e => {
                e.preventDefault();
                const from = +e.dataTransfer.getData('text/plain');
                const to   = idx;
                if (from === to) return;
                const [moved] = gallery.splice(from,1);
                gallery.splice(to,0,moved);
                renumber(); renderGallery();
            });
        });
    }
    function renumber(){ gallery.forEach((g,i)=>g.sort=i); }

    // أزرار الإضافة
    const gAddBtn = document.getElementById('addGalleryBtn');
    const gAddUrl = document.getElementById('addGalleryUrlBtn');
    const gInput  = document.getElementById('galleryFileInput');

    if (gAddBtn) gAddBtn.onclick = () => gInput.click();
    if (gAddUrl) gAddUrl.onclick = async () => {
        const url = prompt('Paste image URL');
        if (!url) return;
        gallery.push({url, alt:'', sort: gallery.length});
        renderGallery();
    };

    if (gInput) gInput.addEventListener('change', async (e) => {
        const files = Array.from(e.target.files || []);
        for (const file of files) {
            const fd = new FormData();
            fd.append('file', file);
            try {
                const res = await fetch('../backend/upload_image.php', { method:'POST', body: fd });
                const j   = await res.json();
                if (!j.ok) throw new Error(j.error || 'Upload failed');
                gallery.push({url: j.url, alt:'', sort: gallery.length});
            } catch(err){ alert(err.message); }
        }
        e.target.value = '';
        renderGallery();
    });
    function renderFabricChips(){
        const wrap = document.getElementById('fabricChips');
        wrap.innerHTML = ['Cotton','Organic Cotton','Bamboo','Modal','Polyester'].map(f => `
    <button type="button" class="chip-toggle${selectedFabrics.has(f) ? ' active' : ''}" onclick="
      selectedFabrics.has('${f}') ? selectedFabrics.delete('${f}') : selectedFabrics.add('${f}');
      renderFabricChips();
    ">${f}</button>
  `).join('');
    }

    async function renderColorChips() {
        const wrap = document.getElementById('colorChips');
        wrap.innerHTML = '';

        try {
            const res = await fetch('../backend/color_options_api.php');
            const j = await res.json();
            if (!j.success) throw new Error(j.error || 'Error loading colors');

            j.colors.forEach(c => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'chip-toggle' + (selectedColors.has(c.name) ? ' active' : '');
                b.textContent = c.name;
                b.style.backgroundColor = c.hex || '#ccc';
                b.onclick = () => {
                    selectedColors.has(c.name)
                        ? selectedColors.delete(c.name)
                        : selectedColors.add(c.name);
                    renderColorChips();
                };
                wrap.appendChild(b);
            });

            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'chip-toggle';
            addBtn.innerHTML = '<i class="fa fa-plus"></i> Add color';
            addBtn.onclick = showAddColorDialog;
            wrap.appendChild(addBtn);

        } catch (err) {
            wrap.innerHTML = '<div style="color:red">Error loading colors</div>';
            console.error(err);
        }
    }





    async function loadCategoryTreeForDrawer() {
        try {
            // جرّب /collections_api.php?withChildren=1 (الوضع B)
            const res = await fetch('../backend/collections_api.php?withChildren=1');
            const ct  = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) throw new Error('Bad JSON');
            const data = await res.json();

            // API عندك ممكن ترجع [{name,slug,children:[{id,name,slug}...]}] أو {ok:true,data:[...]}
            const parents = Array.isArray(data) ? data
                : (Array.isArray(data.data) ? data.data : []);

            // اعمر قائمة الطفل بجروبات كل أب
            const sel = document.getElementById('pChildCat');
            sel.innerHTML = '<option value="">— Select child —</option>';

            parents.forEach(p => {
                const og = document.createElement('optgroup');
                og.label = p.name || p.slug || 'Parent';
                (p.children || []).forEach(ch => {
                    const opt = document.createElement('option');
                    opt.value = ch.id;                                   // مهم: id
                    opt.textContent = ch.name;
                    opt.dataset.parentName = p.name || '';
                    opt.dataset.parentId   = p.id || '';
                    sel.appendChild(opt);
                });
                sel.appendChild(og);
            });

        } catch(e) {
            console.error('loadCategoryTreeForDrawer', e);
        }
    }

    // لما يختار Child حدّث الأب تلقائيًا
    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'pChildCat') {
            const opt = e.target.selectedOptions[0];
            const parentName = opt?.dataset?.parentName || '';
            document.getElementById('pParentCat').value = parentName;
        }
    });
    function showAddColorDialog() {
        document.getElementById('addColorDialog').style.display = 'flex';
        document.getElementById('newColorName').value = '';
        document.getElementById('newColorHex').value = '#DDA0DD';
    }

    function closeAddColorDialog() {
        document.getElementById('addColorDialog').style.display = 'none';
    }

    async function submitNewColor() {
        const name = document.getElementById('newColorName').value.trim();
        const hex = document.getElementById('newColorHex').value.trim();

        if (!name || !/^#[0-9A-Fa-f]{6}$/.test(hex)) {
            alert('Please enter a valid color name and pick a valid color.');
            return;
        }

        try {
            const res = await fetch('../backend/color_options_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, hex })
            });

            const result = await res.json();
            if (!result.success) throw new Error(result.error || 'Failed to add color');

            closeAddColorDialog();
            await renderColorChips(); // reload list
        } catch (err) {
            alert('Error adding color: ' + err.message);
        }
    }

</script>



<script src="../js/adminbar.js" defer></script>
<script src="../js/includeadminBar.js" defer></script>
</body>
</html>