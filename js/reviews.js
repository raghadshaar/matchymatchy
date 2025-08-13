/* Reviews — Matchy Matchy (uses your shared CSS palette) */
(() => {
    const $  = (s,c=document)=>c.querySelector(s);
    const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));
    const LS_KEY = 'mm_reviews_v1';
    const PAGE_SIZE = 5;

    // include sidebar + highlight "Reviews"
    document.addEventListener('DOMContentLoaded', async () => {
        try{
            const html = await fetch('sidebar.html').then(r=>r.text());
            $('#sidebar-container').innerHTML = html;
            $$('a', $('#sidebar-container')).forEach(a=>{
                if((a.textContent||'').trim().toLowerCase()==='reviews') a.classList.add('active');
            });
        }catch{}
    });

    // Seed — Reviews mapped to your products/images
    const seed = [
        {
            id: 1,
            product: {
                name: 'Family Matching Pajama Set',
                sku: 'PJ-FAM-001',
                category: 'Family Matching',
                brand: 'Matchy Matchy',
                image: '../images/familymatching1.jpg'
            },
            reviewer:{ name:'Rana S.', email:'rana@email.com', avatar:'https://i.pravatar.cc/100?img=67' },
            rating: 5,
            text: 'مقاسات دقيقة والخامة مريحة جداً لكل العيلة. أنصح فيه 👌',
            date:'2024-07-21T13:10:00Z',
            status:'Published',
            reply:'شكراً يا رنا! مبسوطين إنه ناسبكم 💚'
        },

        {
            id: 2,
            product: {
                name: 'Kids Sleeveless Summer Jumpsuit',
                sku: 'KJ-002',
                category: 'Kids',
                brand: 'Matchy Matchy',
                image: '../images/kidsjumpsuit.jpg'
            },
            reviewer:{ name:'raghad', email:'raghad.1@email.com', avatar:'https://i.pravatar.cc/100?img=12' },
            rating: 4,
            text: 'خفيف ومناسب للصيف. تمنيت الألوان تكون أفتح شوي.',
            date:'2024-07-27T09:00:00Z',
            status:'Pending'
        },

        {
            id: 3,
            product: {
                name: 'Toddler Disney Pajama Set',
                sku: 'TD-DISNEY-003',
                category: 'Toddler',
                brand: 'Matchy Matchy',
                image: '../images/toddlerdineypjset.jpg'
            },
            reviewer:{ name:'Sara A.', email:'s.ali@email.com', avatar:'https://i.pravatar.cc/100?img=45' },
            rating: 3,
            text: 'التصميم ظريف بس القماش كان أرفع مما توقعت.',
            date:'2024-07-30T17:00:00Z',
            status:'Flagged'
        },

        {
            id: 4,
            product: {
                name: 'Baby Boy Casual Outfit Set',
                sku: 'BB-SET-004',
                category: 'Baby',
                brand: 'Matchy Matchy',
                image: '../images/babyboyclothes.jpg'
            },
            reviewer:{ name:'nedaa', email:'nedaa@email.com', avatar:'https://i.pravatar.cc/100?img=66' },
            rating: 4,
            text: 'طقم عملي ولطيف. الأزرار كانت شوي قاسية بالبداية.',
            date:'2024-07-18T08:45:00Z',
            status:'Published'
        },

        {
            id: 5,
            product: {
                name: 'Baby Girl Ruffle Dress',
                sku: 'BG-DRESS-005',
                category: 'Baby',
                brand: 'Matchy Matchy',
                image: '../images/babygirldress.jpg'
            },
            reviewer:{ name:'Lina Y.', email:'lina@email.com', avatar:'https://i.pravatar.cc/100?img=32' },
            rating: 5,
            text: 'الفستان رهيب والتفاصيل ناعمة. المقاس مضبوط.',
            date:'2024-08-01T12:00:00Z',
            status:'Published'
        },

        {
            id: 6,
            product: {
                name: 'Classic Baby Girl Dress – White Lace',
                sku: 'BG-DRESS-006',
                category: 'Baby',
                brand: 'Matchy Matchy',
                image: '../images/product1.jpg'
            },
            reviewer:{ name:'Hiba M.', email:'hiba@email.com', avatar:'https://i.pravatar.cc/100?img=15' },
            rating: 2,
            text: 'الشكل جميل لكن بعد الغسيل الأول بان عليه وبر بسيط.',
            date:'2024-07-29T10:30:00Z',
            status:'Hidden'
        },

        {
            id: 7,
            product: {
                name: 'Yellow Ruffle Party Dress',
                sku: 'YR-DRESS-007',
                category: 'Kids',
                brand: 'Matchy Matchy',
                image: '../images/yellowdress.jpg'
            },
            reviewer:{ name:'Murad Shaar', email:'mrsh@email.com', avatar:'https://i.pravatar.cc/100?img=5' },
            rating: 5,
            text: 'لونه مشرق وبيطلع رائع بالصور! المقاس يميل يكبر نص مقاس.',
            date:'2024-07-25T11:30:00Z',
            status:'Published'
        },

        {
            id: 8,
            product: {
                name: 'Girls Summer Floral Dress',
                sku: 'SF-DRESS-008',
                category: 'Kids',
                brand: 'Matchy Matchy',
                image: '../images/summerdess.jpg' // إذا اسم الملف عندك summerdress.jpg بدّله هنا
            },
            reviewer:{ name:'Maya T.', email:'maya@email.com', avatar:'https://i.pravatar.cc/100?img=21' },
            rating: 4,
            text: 'خفيييف ومناسب للجو الحار. الطول ممتاز.',
            date:'2024-07-23T14:15:00Z',
            status:'Pending'
        },

        {
            id: 9,
            product: {
                name: 'Girls cute pink cardigan',
                sku: 'PD-Pink-009',
                category: 'Kids',
                brand: 'Matchy Matchy',
                image: '../images/similar3.jpg'
            },
            reviewer:{ name:'Nour K.', email:'nour@email.com', avatar:'https://i.pravatar.cc/100?img=58' },
            rating: 3,
            text: 'دافئ بس تمنيت يكون أنعم شوي من الداخل.',
            date:'2024-08-03T16:40:00Z',
            status:'Published'
        },

        {
            id: 10,
            product: {
                name: 'Pink girl shorts',
                sku: 'ACC-BOW-010',
                category: 'Kids',
                brand: 'Matchy Matchy',
                image: '../images/similar4.jpg'
            },
            reviewer:{ name:'Ola R.', email:'ola@email.com', avatar:'https://i.pravatar.cc/100?img=71' },
            rating: 4,
            text: 'حلوين للتمارين والخروج السريع. اللون لطيف.',
            date:'2024-08-05T09:20:00Z',
            status:'Published'
        },

        {
            id: 11,
            product: {
                name: 'Toddler Disney Pajama Set',
                sku: 'boy-set',
                category: 'Toddler',
                brand: 'Matchy Matchy',
                image: '../images/disneysweatshirtjpg.jpg'
            },
            reviewer:{ name:'Samir H.', email:'samir@email.com', avatar:'https://i.pravatar.cc/100?img=13' },
            rating: 5,
            text: 'ابني حبه كثير، خاصة الرسمة. القياس مضبوط.',
            date:'2024-08-06T18:05:00Z',
            status:'Published'
        }
    ];


    function load(){
        const raw = localStorage.getItem(LS_KEY);
        if(!raw){ localStorage.setItem(LS_KEY, JSON.stringify(seed)); return [...seed]; }
        try{ return JSON.parse(raw) || []; } catch { return [...seed]; }
    }
    function save(list){ localStorage.setItem(LS_KEY, JSON.stringify(list)); }

    let reviews = load();

    /* State */
    let state = { q:'', cat:'', rating:'', status:'', sort:'new', page:1 };

    /* Utils */
    const fmtDate = (d)=> new Date(d).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
    const statusClass = s => s==='Published' ? 'published' : s==='Pending' ? 'pending' : s==='Hidden' ? 'hidden' : 'flagged';


    const starsHTML = (n) => {
        let h = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= n) {
                h += '<i class="fa-solid fa-star full"></i>'; // نجمة مليانة
            } else {
                h += '<i class="fa-regular fa-star empty"></i>'; // نجمة فاضية
            }
        }
        return `<span class="stars">${h}</span>`;
    };

    /* Filters */
    function applyFilters(){
        let list = [...reviews];

        // search: product name/sku/brand + reviewer name/email + text
        const q = state.q.trim().toLowerCase();
        if(q){
            list = list.filter(r =>
                r.product.name.toLowerCase().includes(q) ||
                (r.product.sku||'').toLowerCase().includes(q) ||
                (r.product.brand||'').toLowerCase().includes(q) ||
                r.reviewer.name.toLowerCase().includes(q) ||
                r.reviewer.email.toLowerCase().includes(q) ||
                r.text.toLowerCase().includes(q)
            );
        }
        if(state.cat)   list = list.filter(r => r.product.category === state.cat);




        if (state.rating) {
            const wanted = Number(state.rating);
            list = list.filter(r => Number(r.rating) === wanted); // exact match
        }

        if(state.status) list = list.filter(r => r.status === state.status);

        // sort
        if(state.sort==='new') list.sort((a,b)=> new Date(b.date)-new Date(a.date));
        if(state.sort==='old') list.sort((a,b)=> new Date(a.date)-new Date(b.date));
        if(state.sort==='high') list.sort((a,b)=> b.rating-a.rating || new Date(b.date)-new Date(a.date));
        if(state.sort==='low') list.sort((a,b)=> a.rating-b.rating || new Date(b.date)-new Date(a.date));

        return list;
    }

    function paginate(list){
        const total = list.length;
        const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if(state.page > pages) state.page = pages;
        const start = (state.page-1)*PAGE_SIZE;
        return { slice:list.slice(start,start+PAGE_SIZE), total, pages };
    }

    /* Render */
    function render(){
        const filtered = applyFilters();
        const {slice, total, pages} = paginate(filtered);

        const html = slice.map(r => `
      <div class="review-item">
        <div class="prod-meta">
          <div class="prod-thumb"><img src="${r.product.image}" alt=""></div>
          <div class="prod-info">
            <h4>${r.product.name}</h4>
            <div class="muted">SKU: ${r.product.sku}</div>
            <div class="muted">${r.product.brand}</div>
            <div class="prod-chips">
              <span class="chip cat">${r.product.category}</span>
            </div>
          </div>
        </div>

        <div>
          <div class="rev-head">
            <div class="rev-person">
              <img class="avatar" src="${r.reviewer.avatar}" alt="">
              <div class="meta">
                <strong>${r.reviewer.name}</strong>
                <small>${fmtDate(r.date)} • ${r.reviewer.email}</small>
              </div>
              <div class="stars" aria-label="${r.rating} stars">${starsHTML(r.rating)}</div>
            </div>
            <span class="badge ${statusClass(r.status)}">${r.status}</span>
          </div>

          <div class="rev-text">${escapeHTML(r.text)}</div>
          ${r.reply ? `<div class="reply"><strong>Admin reply</strong><div>${escapeHTML(r.reply)}</div></div>` : ''}

          <div class="actions">
            ${r.status==='Pending'
            ? `<button class="action teal" data-approve="${r.id}"><i class="fa-solid fa-check"></i> Approve</button>`
            : `<button class="action teal" data-publish="${r.id}"><i class="fa-solid fa-upload"></i> Publish</button>`}
            <button class="action ghost" data-hide="${r.id}"><i class="fa-solid fa-eye-slash"></i> Hide</button>
            <button class="action pink"  data-flag="${r.id}"><i class="fa-solid fa-flag"></i> Flag</button>
            <button class="action ghost" data-reply="${r.id}"><i class="fa-solid fa-reply"></i> Reply</button>
            <button class="action pink"  data-del="${r.id}"><i class="fa-solid fa-trash"></i> Delete</button>
          </div>
        </div>
      </div>
    `).join('');

        $('#reviewsList').innerHTML = html;
        $('#emptyState').style.display = filtered.length ? 'none' : 'block';
        $('#pagerInfo').textContent = filtered.length
            ? `Showing ${Math.min((state.page-1)*PAGE_SIZE+1,total)}–${Math.min(state.page*PAGE_SIZE,total)} of ${total} reviews`
            : 'No results';

        // page buttons
        const wrap = $('#pageNums'); wrap.innerHTML='';
        for(let i=1;i<=pages;i++){
            const b=document.createElement('button');
            b.className='page-btn'+(i===state.page?' active':'');
            b.textContent=i;
            b.onclick=()=>{ state.page=i; render(); };
            wrap.appendChild(b);
        }
        $('#prevPage').disabled = state.page<=1;
        $('#nextPage').disabled = state.page>=pages;

        wireRowActions();
    }

    /* Row actions */
    function wireRowActions(){
        $$('[data-approve]').forEach(b=> b.onclick = ()=> setStatus(+b.dataset.approve,'Published'));
        $$('[data-publish]').forEach(b=> b.onclick = ()=> setStatus(+b.dataset.publish,'Published'));
        $$('[data-hide]').forEach(b=> b.onclick = ()=> setStatus(+b.dataset.hide,'Hidden'));
        $$('[data-flag]').forEach(b=> b.onclick = ()=> setStatus(+b.dataset.flag,'Flagged'));
        $$('[data-del]').forEach(b=> b.onclick = ()=> remove(+b.dataset.del));
        $$('[data-reply]').forEach(b=> b.onclick = ()=> reply(+b.dataset.reply));
    }

    function setStatus(id, s){
        const i = reviews.findIndex(r=>r.id===id); if(i<0) return;
        reviews[i].status = s;
        save(reviews); render();
    }
    function remove(id){
        const r = reviews.find(x=>x.id===id);
        if(r && confirm(`Delete review by "${r.reviewer.name}" for "${r.product.name}"?`)){
            reviews = reviews.filter(x=>x.id!==id);
            save(reviews); render();
        }
    }
    function reply(id){
        const i = reviews.findIndex(r=>r.id===id); if(i<0) return;
        const txt = prompt('Write your reply:', reviews[i].reply || '');
        if(txt!==null){
            reviews[i].reply = txt.trim();
            save(reviews); render();
        }
    }

    /* Events — filters */
    $('#q').oninput     = e=>{ state.q = e.target.value; state.page=1; render(); };
    $('#cat').onchange  = e=>{ state.cat = e.target.value; state.page=1; render(); };
    $('#rating').onchange = e=>{ state.rating = e.target.value; state.page=1; render(); };
    $('#sort').onchange = e=>{ state.sort = e.target.value; state.page=1; render(); };

    $('#clearFilters').onclick = ()=>{
        state = { q:'', cat:'', rating:'', status:'', sort:'new', page:1 };
        $('#q').value=''; $('#cat').value=''; $('#rating').value=''; $('#sort').value='new';
        $$('#statusTabs .pill').forEach(p=>p.classList.remove('active'));
        $$('#statusTabs .pill')[0].classList.add('active');
        render();
    };

    // status pills
    $$('#statusTabs .pill').forEach(p=>{
        p.onclick = ()=>{
            $$('#statusTabs .pill').forEach(x=>x.classList.remove('active'));
            p.classList.add('active');
            state.status = p.dataset.status || '';
            state.page = 1;
            render();
        };
    });

    // pager
    $('#prevPage').onclick = ()=>{ if(state.page>1){ state.page--; render(); } };
    $('#nextPage').onclick = ()=>{ state.page++; render(); };

    // safe text
    function escapeHTML(s){ return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

    render();
})();

// ===== Toolbar state (plug into your existing filter logic) =====
const state = window.reviewState || { q:'', category:'', status:'', minRating:0, sort:'new' };

// Search
const $ = (s,c=document)=>c.querySelector(s);
const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));

$('#revSearch').addEventListener('input', e=>{
    state.q = e.target.value.trim().toLowerCase();
    renderReviews();
});

// Category
$('#revCategory').addEventListener('change', e=>{
    state.category = e.target.value;
    renderReviews();
});

// Sort
$('#revSort').addEventListener('change', e=>{
    state.sort = e.target.value;
    renderReviews();
});

// Clear
$('#revClear').addEventListener('click', ()=>{
    state.q=''; state.category=''; state.minRating=0; state.status=''; state.sort='new';
    $('#revSearch').value=''; $('#revCategory').value=''; $('#revSort').value='new';
    setStatusActive(''); setRatingLabel(0);
    renderReviews();
});

// Status pills (toggle active + teal)
function setStatusActive(val){
    $$('#statusSeg .seg-btn').forEach(b=>{
        b.classList.toggle('active', b.dataset.s===val);
    });
}
$('#statusSeg').addEventListener('click', e=>{
    const btn = e.target.closest('.seg-btn'); if(!btn) return;
    state.status = btn.dataset.s || '';
    setStatusActive(state.status);
    renderReviews();
});
setStatusActive(''); // default All

// Rating dropdown with stars
const ratingBtn = $('#ratingBtn');
const ratingMenu = $('#ratingMenu');
const ratingLabel = $('#ratingLabel');

function setRatingLabel(min){
    if(min<=0){ ratingLabel.textContent = 'All Ratings'; ratingBtn.classList.remove('active'); return; }
    ratingLabel.textContent = `${min} Stars & up`;
    ratingBtn.classList.add('active');
}
function closeRating(){ ratingMenu.classList.remove('show'); ratingBtn.setAttribute('aria-expanded','false'); }
function openRating(){ ratingMenu.classList.add('show'); ratingBtn.setAttribute('aria-expanded','true'); }

// Paint star counts in menu
$$('.rating-item .stars', ratingMenu).forEach(el=>{
    const v = Number(el.dataset.v||0);
    el.style.setProperty('--n', v);
});

// Toggle menu
ratingBtn.addEventListener('click', ()=>{
    const open = ratingMenu.classList.contains('show');
    if(open) closeRating(); else openRating();
});
// Pick rating
ratingMenu.addEventListener('click', e=>{
    const item = e.target.closest('.rating-item'); if(!item) return;
    const v = Number(item.dataset.min||0);
    state.minRating = v;
    setRatingLabel(v);
    closeRating();
    renderReviews();
});
// Click-away
document.addEventListener('click', (e)=>{
    if(!ratingMenu.contains(e.target) && !ratingBtn.contains(e.target)) closeRating();
});

// ===== Hook into your current render =====
// Example filter helpers (adapt إلى دوالّك الحالية):
function passesStatus(r){
    return !state.status || r.status===state.status;
}
function passesCategory(r){
    return !state.category || (r.productCategory===state.category);
}
function passesRating(r){
    return r.rating >= (state.minRating||0);
}

// استدعِ renderReviews() في أماكن التحديث — داخلها طبّق الفلاتر:
function renderReviews(){
    // مثال:
    // const list = allReviews
    //   .filter(r => (!state.q || (r.text+r.userName+r.productName).toLowerCase().includes(state.q)))
    //   .filter(passesStatus)
    //   .filter(passesCategory)
    //   .filter(passesRating);
    // sort by state.sort …
    // ثمّ ارسم العناصر
}



// Return HTML for 0..5 with halves supported (e.g. 3.5)


(function(){
    function enhanceSelect(sel){
        // اخفي الأصلي وخليه يشتغل للفورم
        sel.classList.add('ui-select-hidden');

        // ابني واجهة بديلة
        const wrap = document.createElement('div');
        wrap.className = 'mm-select';
        wrap.setAttribute('aria-expanded','false');

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mm-select__btn';
        const label = document.createElement('span');
        label.className = 'mm-select__label';
        const chev = document.createElement('i');
        chev.className = 'mm-select__chev fa-solid fa-chevron-down';
        btn.append(label, chev);

        const menu = document.createElement('div');
        menu.className = 'mm-select__menu';
        // اصنع الخيارات من <option>
        Array.from(sel.options).forEach((opt, idx)=>{
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'mm-option';
            row.setAttribute('role','option');
            row.dataset.value = opt.value;
            row.innerHTML = `<span>${opt.textContent}</span><i class="fa-solid fa-check" style="visibility:hidden"></i>`;
            if(opt.selected){
                row.setAttribute('aria-selected','true');
                row.querySelector('i').style.visibility = 'visible';
                label.textContent = opt.textContent;
            }
            row.addEventListener('click', ()=>{
                // حدّث الواجهة والـ select الأصلي
                menu.querySelectorAll('.mm-option').forEach(o=>{
                    o.setAttribute('aria-selected','false');
                    o.querySelector('i').style.visibility = 'hidden';
                });
                row.setAttribute('aria-selected','true');
                row.querySelector('i').style.visibility = 'visible';
                label.textContent = opt.textContent;
                sel.value = opt.value;
                sel.dispatchEvent(new Event('change', {bubbles:true}));
                close();
            });
            menu.appendChild(row);
        });

        // قيمه افتراضية لو ما في selected
        if(!label.textContent) label.textContent = sel.options[sel.selectedIndex]?.text || sel.options[0]?.text || '';

        function open(){ wrap.setAttribute('aria-expanded','true'); }
        function close(){ wrap.setAttribute('aria-expanded','false'); }
        function toggle(){ (wrap.getAttribute('aria-expanded')==='true') ? close() : open(); }

        btn.addEventListener('click', toggle);

        // إغلاق عند الكليك خارج القائمة
        document.addEventListener('click', (e)=>{
            if(!wrap.contains(e.target)) close();
        });

        // كيبورد بسيط: Enter/Space لفتح وإغلاق، Esc يغلق
        btn.addEventListener('keydown', (e)=>{
            if(e.key==='Enter' || e.key===' '){ e.preventDefault(); toggle(); }
            if(e.key==='Escape'){ close(); }
        });
        menu.addEventListener('keydown', (e)=>{
            if(e.key==='Escape'){ e.preventDefault(); close(); btn.focus(); }
        });

        // ادخل الواجهة مكان الـ select
        sel.parentNode.insertBefore(wrap, sel);
        wrap.append(btn, menu, sel);
    }

    // فعّل لكل سيلكت في التولبار
    window.addEventListener('DOMContentLoaded', ()=>{
        document.querySelectorAll('.toolbar .field select').forEach(enhanceSelect);
    });
})();

