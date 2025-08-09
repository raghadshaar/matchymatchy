// include.js
function includeHTML() {
  const elements = document.querySelectorAll('[data-include]');
  elements.forEach(el => {
    const file = el.getAttribute('data-include');
    fetch(file)
      .then(response => {
        if (!response.ok) throw new Error("Page not found");
        return response.text();
      })
      .then(data => {
        el.innerHTML = data;
      })
      .catch(error => {
        el.innerHTML = "Include failed.";
      });
  });
}

document.addEventListener("DOMContentLoaded", includeHTML);