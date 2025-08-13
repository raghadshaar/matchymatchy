const $ = (s,c=document)=>c.querySelector(s);
const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));
const LS_KEY = 'mm_settings_v2';

const defaults = {
    // General
    store_name: 'Matchy Matchy',
    store_tagline: 'Perfect matches for every style',
    store_description: 'Discover the perfect fashion matches with our curated collection of trendy clothing and accessories.',
    primary_color: '#008080',
    contact_email: 'hello@matchymatchy.com',
    contact_phone: '+1 (555) 123-4567',
    contact_address: '123 Fashion Street, Style District, New York, NY 10001',
    logo_dataURL: '',

    // Store
    currency: 'ILS',
    country: 'Palestine',
    language: 'en',
    timezone: 'Asia/Jerusalem',
    show_tax: true,
    inv_tracking: true,
    guest_checkout: false,

    // Payment
    pay_paypal: true,
    paypal_client: '',
    paypal_secret: '',
    pay_stripe: true,
    stripe_pub: '',
    stripe_secret: '',
    pay_cod: false,

    // Shipping
    shipping: [
        { id: 1, name: 'Standard Shipping', desc: '5–7 business days', price: 5.99 },
        { id: 2, name: 'Express Shipping',  desc: '2–3 business days', price: 12.99 },
        { id: 3, name: 'Overnight Shipping',desc: 'Next business day', price: 24.99 }
    ],
    ship_free_enable: true,
    ship_free_threshold: 75,

    // Notifications
    notif_email: 'notifications@matchymatchy.com',
    notif_new_order: true,
    notif_low_stock: true,
    notif_customer_reg: false,
    sms_provider: 'twilio',
    sms_api_key: '',
    sms_enable: false
};

function load(){
    const raw = localStorage.getItem(LS_KEY);
    if(!raw){ localStorage.setItem(LS_KEY, JSON.stringify(defaults)); return {...defaults}; }
    try{ return {...defaults, ...JSON.parse(raw)}; }catch{ return {...defaults}; }
}
function save(data){ localStorage.setItem(LS_KEY, JSON.stringify(data)); }

/* state */
let state = load();

document.addEventListener('DOMContentLoaded', () => {
    bindTabs();
    bindColor();
    bindLogo();
    bindSaveReset();
    bindAutoSave();
    bindShippingUI();
    hydrate();
    updateCurrencyPrefix();
});

/* Tabs */
function bindTabs(){
    $$('.settings-tabs .tab').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            $$('.settings-tabs .tab').forEach(b=>b.classList.toggle('active', b===btn));
            const tab = btn.dataset.tab;
            $$('.panel').forEach(p=>p.classList.toggle('active', p.id === `panel-${tab}`));
        });
    });
}

/* Color */
function bindColor(){
    const input = $('#primary_color');
    const picker = $('#primary_color_picker');
    const swatch = $('#colorSwatch');
    const setColor = v=>{
        const val = normalizeHex(v) || '#008080';
        input.value = val; picker.value = val; swatch.style.background = val;
    };
    input.addEventListener('input', e=> setColor(e.target.value));
    picker.addEventListener('input', e=> setColor(e.target.value));
    setColor(state.primary_color);
}
function normalizeHex(v){
    if(!v) return null; let s=v.trim(); if(!s.startsWith('#')) s='#'+s;
    return /^#([0-9a-f]{6}|[0-9a-f]{8})$/i.test(s)? s.toUpperCase(): null;
}

/* Logo */
function bindLogo(){
    const drop = $('#logoDrop'), fileInput = $('#logoInput'), choose = $('#chooseLogo'), img = $('#logoPreview');
    const showPreview = dataURL => { if(dataURL){ img.src=dataURL; img.style.display='block'; } else { img.removeAttribute('src'); img.style.display='none'; } };
    const readFile = f => { const r=new FileReader(); r.onload=e=>{ state.logo_dataURL=e.target.result; showPreview(state.logo_dataURL); scheduleAutoSave(200); }; r.readAsDataURL(f); };
    choose.addEventListener('click', ()=> fileInput.click());
    fileInput.addEventListener('change', e=>{ const f=e.target.files?.[0]; if(f) readFile(f); });
    ;['dragenter','dragover'].forEach(ev=> drop.addEventListener(ev, e=>{ e.preventDefault(); drop.classList.add('drag'); }));
    ;['dragleave','drop'].forEach(ev=> drop.addEventListener(ev, e=>{ e.preventDefault(); drop.classList.remove('drag'); }));
    drop.addEventListener('drop', e=>{ const f=e.dataTransfer.files?.[0]; if(f) readFile(f); });
    showPreview(state.logo_dataURL);
}

/* Footer auto-save */
const autoMsg = ()=>$('#autoMsg');
let saveTimer;
function setAutoMsg(stateText){
    const el = autoMsg();
    if(!el) return;
    if(stateText==='saving'){ el.className='auto-msg saving'; el.innerHTML='<i class="fa-regular fa-clock"></i> Saving…'; }
    else if(stateText==='saved'){ el.className='auto-msg saved'; el.innerHTML='<i class="fa-regular fa-circle-check"></i> All changes saved'; }
    else { el.className='auto-msg'; el.innerHTML='<i class="fa-regular fa-circle-check"></i> Changes will be saved automatically'; }
}
function scheduleAutoSave(delay=600){
    setAutoMsg('saving'); clearTimeout(saveTimer);
    saveTimer=setTimeout(()=>{ collect(); save(state); setAutoMsg('saved'); }, delay);
}
function bindAutoSave(){
    const inputs = document.querySelectorAll('#settingsForm input, #settingsForm select, #settingsForm textarea');
    inputs.forEach(el=>{
        el.addEventListener('input', ()=>scheduleAutoSave());
        el.addEventListener('change', ()=>scheduleAutoSave());
    });
}

/* Save / Reset buttons */
function bindSaveReset(){
    $('#btnSave')?.addEventListener('click', ()=>{ collect(); save(state); setAutoMsg('saved'); flash('Saved!'); });
    $('#btnSaveBottom')?.addEventListener('click', ()=>{ collect(); save(state); setAutoMsg('saved'); });
    $('#btnReset')?.addEventListener('click', ()=>{
        if(confirm('Reset settings to defaults?')){
            state={...defaults}; save(state); hydrate(); setAutoMsg(); flash('Reset done.');
        }
    });
    $('#btnCancel')?.addEventListener('click', ()=>{ state=load(); hydrate(); setAutoMsg(); });
}

/* Shipping UI */
function bindShippingUI(){
    $('#btnAddShip').addEventListener('click', ()=>{
        const id = Date.now();
        state.shipping.push({ id, name:'New Shipping', desc:'Edit details', price:0 });
        renderShipList(); scheduleAutoSave(300);
    });
    renderShipList();
}
function renderShipList(){
    const list = $('#ship_list');
    list.innerHTML = state.shipping.map(s=>`
    <div class="ship-item" data-id="${s.id}">
      <div class="ship-meta">
        <div class="title"><input class="input ship-name" value="${s.name}"></div>
        <div class="hint"><input class="input ship-desc" value="${s.desc}"></div>
      </div>
      <div class="ship-actions">
        <div class="ship-price">
          <input class="input ship-price-input" type="number" step="0.01" min="0" value="${s.price}">
        </div>
        <button type="button" class="ship-del" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
      </div>
    </div>
  `).join('');

    list.querySelectorAll('.ship-item').forEach(row=>{
        const id = +row.dataset.id;
        row.querySelector('.ship-name').addEventListener('input', e=>{ getShip(id).name=e.target.value; scheduleAutoSave(300); });
        row.querySelector('.ship-desc').addEventListener('input', e=>{ getShip(id).desc=e.target.value; scheduleAutoSave(300); });
        row.querySelector('.ship-price-input').addEventListener('input', e=>{ getShip(id).price=Number(e.target.value||0); scheduleAutoSave(300); });
        row.querySelector('.ship-del').addEventListener('click', ()=>{ state.shipping = state.shipping.filter(x=>x.id!==id); renderShipList(); scheduleAutoSave(300); });
    });
}
function getShip(id){ return state.shipping.find(x=>x.id===id); }

/* Helpers */
function flash(text){
    const el=document.createElement('div');
    Object.assign(el.style,{position:'fixed',right:'16px',bottom:'16px',background:'var(--teal)',color:'#fff',padding:'10px 14px',borderRadius:'12px',boxShadow:'var(--shadow)',zIndex:1000,fontWeight:'600'});
    el.textContent=text; document.body.appendChild(el); setTimeout(()=>el.remove(),1500);
}
function updateCurrencyPrefix(){
    const prefix = $('#currencyPrefix');
    const m = { ILS:'₪', USD:'$', EUR:'€' };
    prefix.textContent = m[state.currency] || '$';
}

/* Hydrate & Collect */
function hydrate(){
    // General
    $('#store_name').value = state.store_name;
    $('#store_tagline').value = state.store_tagline;
    $('#store_description').value = state.store_description;
    $('#primary_color').value = state.primary_color;
    $('#primary_color_picker').value = state.primary_color;
    $('#colorSwatch').style.background = state.primary_color;
    $('#contact_email').value = state.contact_email;
    $('#contact_phone').value = state.contact_phone;
    $('#contact_address').value = state.contact_address;
    if(state.logo_dataURL){ $('#logoPreview').src=state.logo_dataURL; $('#logoPreview').style.display='block'; }

    // Store
    $('#currency').value = state.currency;
    $('#country').value = state.country;
    $('#language').value = state.language;
    $('#timezone').value = state.timezone;
    $('#show_tax').checked = !!state.show_tax;
    $('#inv_tracking').checked = !!state.inv_tracking;
    $('#guest_checkout').checked = !!state.guest_checkout;

    // Payment
    $('#pay_paypal').checked = !!state.pay_paypal;
    $('#paypal_client').value = state.paypal_client;
    $('#paypal_secret').value = state.paypal_secret;

    $('#pay_stripe').checked = !!state.pay_stripe;
    $('#stripe_pub').value = state.stripe_pub;
    $('#stripe_secret').value = state.stripe_secret;

    $('#pay_cod').checked = !!state.pay_cod;

    // Shipping
    renderShipList();
    $('#ship_free_enable').checked = !!state.ship_free_enable;
    $('#ship_free_threshold').value = state.ship_free_threshold;

    // Notifications
    $('#notif_email').value = state.notif_email;
    $('#notif_new_order').checked = !!state.notif_new_order;
    $('#notif_low_stock').checked = !!state.notif_low_stock;
    $('#notif_customer_reg').checked = !!state.notif_customer_reg;
    $('#sms_provider').value = state.sms_provider;
    $('#sms_api_key').value = state.sms_api_key;
    $('#sms_enable').checked = !!state.sms_enable;

    updateCurrencyPrefix();
}
function collect(){
    // General
    state.store_name = $('#store_name').value.trim();
    state.store_tagline = $('#store_tagline').value.trim();
    state.store_description = $('#store_description').value.trim();
    state.primary_color = normalizeHex($('#primary_color').value) || '#008080';
    state.contact_email = $('#contact_email').value.trim();
    state.contact_phone = $('#contact_phone').value.trim();
    state.contact_address = $('#contact_address').value.trim();

    // Store
    state.currency = $('#currency').value; updateCurrencyPrefix();
    state.country = $('#country').value;
    state.language = $('#language').value;
    state.timezone = $('#timezone').value;
    state.show_tax = $('#show_tax').checked;
    state.inv_tracking = $('#inv_tracking').checked;
    state.guest_checkout = $('#guest_checkout').checked;

    // Payment
    state.pay_paypal = $('#pay_paypal').checked;
    state.paypal_client = $('#paypal_client').value.trim();
    state.paypal_secret = $('#paypal_secret').value;

    state.pay_stripe = $('#pay_stripe').checked;
    state.stripe_pub = $('#stripe_pub').value.trim();
    state.stripe_secret = $('#stripe_secret').value;

    state.pay_cod = $('#pay_cod').checked;

    // Shipping
    state.ship_free_enable = $('#ship_free_enable').checked;
    state.ship_free_threshold = Number($('#ship_free_threshold').value||0);

    // Notifications
    state.notif_email = $('#notif_email').value.trim();
    state.notif_new_order = $('#notif_new_order').checked;
    state.notif_low_stock = $('#notif_low_stock').checked;
    state.notif_customer_reg = $('#notif_customer_reg').checked;
    state.sms_provider = $('#sms_provider').value;
    state.sms_api_key = $('#sms_api_key').value;
    state.sms_enable = $('#sms_enable').checked;
}






