// ../js/includeadminBar.js
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-include]').forEach(async (holder) => {
        const url = holder.getAttribute('data-include');
        try {
            const html = await fetch(url).then(r => r.text());
            holder.innerHTML = html;

            document.dispatchEvent(new CustomEvent('admin:include:done', {
                detail: { root: holder }
            }));

            if (window.initTopbar) window.initTopbar();
        } catch (e) {
            console.error('Include failed:', e);
        }
    });
});
