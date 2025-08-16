// /js/header.js
window.setupHeaderSearch = function setupHeaderSearch(){
    const input = document.querySelector('.search-box input');
    if (!input) return;

    // Enter
    input.addEventListener('keydown', (e)=>{
        if (e.key === 'Enter') {
            const q = input.value.trim();
            if (q) location.href = `category.html?q=${encodeURIComponent(q)}&page=1`;
        }
    });

    const icon = document.querySelector('.search-box i');
    icon?.addEventListener('click', ()=>{
        const q = input.value.trim();
        if (q) location.href = `category.html?q=${encodeURIComponent(q)}&page=1`;
    });
};