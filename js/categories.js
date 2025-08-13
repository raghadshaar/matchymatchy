/* Categories — Admin */
(() => {
    const $ = (s,c=document)=>c.querySelector(s);
    const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));

    const LS_KEY = 'mm_categories_v1';

    // include sidebar + activate link
    document.addEventListener('DOMContentLoaded', async () => {
        try{
            const html = await fetch('sidebar.html').then(r=>r.text());
            $('#sidebar-container').innerHTML = html;
            $$('a', $('#sidebar-container')).forEach(a=>{
                if((a.textContent||'').trim().toLowerCase()==='categories') a.classList.add('active');
            });
        }catch{}
    });

    // seed
    const seed = [
        {id:1, name:'Baby',        desc:'Clothing and accessories for babies 0–24 months', status:'Active', parent:'', products:156, image:'https://images.unsplash.com/photo-1544006659-f0b21884ce1d?w=1200&q=80'},
        {id:2, name:'Toddler',     desc:'Fun & comfy clothes for toddlers 2–4 yrs',        status:'Active', parent:'', products:89,  image:'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=1200&q=80'},
        {id:3, name:'Kids',        desc:'Clothing for kids 5–12 yrs old',                   status:'Active', parent:'', products:203, image:'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=1200&q=80'},
        {id:4, name:'Family Matching', desc:'Coordinated outfits for the whole family',    status:'Active', parent:'', products:67,  image:'https://images.unsplash.com/photo-1516534775068-ba3e7458af70?w=1200&q=80'},
        {id:5, name:'Baby Accessories', desc:'Hats, bibs, socks and essentials',           status:'Active', parent:'Baby', products:42, image:'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=1200&q=80'},
        {id:6, name:'Kids Footwear', desc:'Comfortable shoes & sneakers',                  status:'Active', parent:'Kids', products:78, image:'https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&q=80'},
        {id:7, name:'Premium Brands', desc:'Designer & premium brand collections',         status:'Active', parent:'', products:34, image:'https://images.unsplash.com/photo-1543076447-215ad9ba6923?w=1200&q=80'},
        {id:8, name:'Seasonal Deals', desc:'Special offers & seasonal promotions',         status:'Hidden', parent:'', products:23, image:'https://images.unsplash.com/photo-1520975682031-ae7c24058b2e?w=1200&q=80'}
    ];

    function load(){
        const raw = localStorage.getItem(LS_KEY);
        if(!raw){ localStorage.setItem(LS_KEY, JSON.stringify(seed)); return [...seed]; }
        try{ return JSON.parse(raw)||[] }catch{ return [...seed]; }
    }
    function save(list){ localStorage.setItem(LS_KEY, JSON.stringify(list)); }

    let cats = load();

    /* state */
    let state = { q:'', parent:'', status:'' };

    /* helpers */
    function refreshParentOptions(){
        const roots = [''].concat([...new Set(cats.map(c=>c.parent))].filter(Boolean), cats.filter(c=>!c.parent).map(c=>c.name));
        const unique = [...new Set(roots)].filter(Boolean).sort();
        $('#parentFilter').innerHTML = `<option value="">All Parents</option>` + unique.map(p=>`<option>${p}</option>`).join('');
        $('#cParent').innerHTML = `<option value="">None (Top Level)</option>` + cats.filter(c=>!c.parent).map(c=>`<option>${c.name}</option>`).join('');
    }
    refreshParentOptions();

    const statusBadge = (s)=> s==='Active' ? 'active' : 'hidden';

    /* filters */
    function applyFilters(){
        let list = [...cats];
        const q = state.q.trim().toLowerCase();
        if(q) list = list.filter(c => c.name.toLowerCase().includes(q) || (c.desc||'').toLowerCase().includes(q));
        if(state.parent) list = list.filter(c => (c.parent||'')===state.parent);
        if(state.status) list = list.filter(c => c.status===state.status);
        return list;
    }

    /* render */
    function render(){
        const list = applyFilters();
        $('#catCount').textContent = cats.length;

        $('#grid').innerHTML = list.map(c=>`
      <div class="cat-card">
        <div class="cat-media">${c.image?`<img src="${c.image}" alt="">`:''}</div>
        <div class="cat-body">
          <div class="cat-title">
            <h4>${c.name}</h4>
            <span class="badge ${statusBadge(c.status)}">${c.status}</span>
          </div>
          <div class="cat-desc">${escapeHTML(c.desc||'')}</div>
          <div class="cat-meta">
            <span><i class="fa-regular fa-folder-open"></i> ${c.products||0} products</span>
            ${c.parent?`<span><i class="fa-solid fa-sitemap"></i> ${c.parent}</span>`:''}
          </div>
        </div>
        <div class="card-actions">
          <button class="btn btn-ghost" title="Edit" data-edit="${c.id}"><i class="fa-regular fa-pen-to-square"></i></button>
          <button class="btn btn-ghost" title="Delete" data-del="${c.id}"><i class="fa-regular fa-trash-can"></i></button>
        </div>
      </div>
    `).join('');

        $('#emptyState').style.display = list.length ? 'none' : 'block';

        // actions
        $$('[data-edit]').forEach(b=> b.onclick=()=>openEdit(+b.dataset.edit));
        $$('[data-del]').forEach(b=> b.onclick=()=>removeCat(+b.dataset.del));
    }
    render();

    /* toolbar events */
    $('#q').oninput = e=>{ state.q=e.target.value; render(); };
    $('#parentFilter').onchange = e=>{ state.parent=e.target.value; render(); };
    $('#statusFilter').onchange = e=>{ state.status=e.target.value; render(); };
    $('#clearFilters').onclick = ()=>{ state={q:'',parent:'',status:''}; $('#q').value=''; $('#parentFilter').value=''; $('#statusFilter').value=''; render(); };
    $('#addCatBtn').onclick = openAdd;
    $('#emptyAdd').onclick = openAdd;

    /* drawer */
    const drawer = $('#drawer'), backdrop = $('#drawerBackdrop');
    const els = {name:$('#cName'), desc:$('#cDesc'), parent:$('#cParent'), statusName:'cStatus', preview:$('#preview'), drop:$('#drop'), file:$('#fileInput')};
    let editingId = null;
    let uploadedDataUrl = ''; // store preview dataurl

    function openAdd(){
        editingId = null; uploadedDataUrl='';
        $('#drawerTitle').textContent='Add New Category';
        els.name.value=''; els.desc.value=''; els.parent.value=''; $$(`input[name="${els.statusName}"][value="Active"]`)[0].checked=true;
        els.preview.src=''; els.preview.style.display='none'; els.drop.classList.remove('has-img');
        openDrawer();
    }
    function openEdit(id){
        const c = cats.find(x=>x.id===id); if(!c) return;
        editingId = id; uploadedDataUrl = c.image||'';
        $('#drawerTitle').textContent='Edit Category';
        els.name.value=c.name; els.desc.value=c.desc||''; els.parent.value=c.parent||'';
        $$(`input[name="${els.statusName}"]`).forEach(r=>r.checked=(r.value===c.status));
        if(c.image){ els.preview.src=c.image; els.preview.style.display='block'; els.drop.classList.add('has-img'); }
        else { els.preview.src=''; els.preview.style.display='none'; els.drop.classList.remove('has-img'); }
        openDrawer();
    }
    function openDrawer(){ drawer.classList.add('open'); backdrop.classList.add('show'); }
    function closeDrawer(){ drawer.classList.remove('open'); backdrop.classList.remove('show'); }

    $('#cancelDrawer').onclick = closeDrawer;
    backdrop.onclick = closeDrawer;

    // upload preview (drag & drop or click)
    els.drop.addEventListener('click', ()=> els.file.click());
    els.file.addEventListener('change', handleFile);
    els.drop.addEventListener('dragover', e=>{e.preventDefault(); els.drop.classList.add('drag');});
    els.drop.addEventListener('dragleave', ()=> els.drop.classList.remove('drag'));
    els.drop.addEventListener('drop', e=>{
        e.preventDefault(); els.drop.classList.remove('drag');
        const f = e.dataTransfer.files?.[0]; if(f) readFile(f);
    });

    function handleFile(e){
        const f = e.target.files?.[0];
        if(f) readFile(f);
    }
    function readFile(file){
        if(!file.type.startsWith('image/')) return alert('Please choose an image file.');
        const rd = new FileReader();
        rd.onload = () => {
            uploadedDataUrl = rd.result;
            els.preview.src = uploadedDataUrl;
            els.preview.style.display='block';
            els.drop.classList.add('has-img');
        };
        rd.readAsDataURL(file);
    }

    $('#saveCat').onclick = ()=>{
        const name = els.name.value.trim();
        if(!name) return alert('Please enter category name.');
        const desc = els.desc.value.trim();
        const parent = els.parent.value;
        const status = $$(`input[name="${els.statusName}"]`).find(r=>r.checked)?.value || 'Active';

        if(editingId){
            cats = cats.map(c=> c.id===editingId ? {...c, name, desc, parent, status, image:uploadedDataUrl} : c);
        }else{
            const next = (cats.reduce((m,c)=>Math.max(m,c.id),0)||0)+1;
            cats.push({id:next, name, desc, parent, status, products:0, image:uploadedDataUrl});
        }
        save(cats);
        refreshParentOptions();
        render();
        closeDrawer();
    };

    function removeCat(id){
        const c = cats.find(x=>x.id===id); if(!c) return;
        if(c.products>0){ alert('This category has products. Reassign or empty it before deleting.'); return; }
        if(confirm(`Delete category "${c.name}"?`)){
            cats = cats.filter(x=>x.id!==id);
            save(cats); refreshParentOptions(); render();
        }
    }

    function escapeHTML(s){ return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
})();
