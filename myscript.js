
// SLIDER
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');
const total = slides.length;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (dots[i]) dots[i].classList.remove('active');
        if (i === index) {
            slide.classList.add('active');
            if (dots[i]) dots[i].classList.add('active');
        }
    });
    currentSlide = index;
}

function nextSlide() {
    const next = (currentSlide + 1) % total;
    showSlide(next);
}

function prevSlide() {
    const prev = (currentSlide - 1 + total) % total;
    showSlide(prev);
}

function goToSlide(index) {
    showSlide(index);
}

setInterval(nextSlide, 5000);

// ✅ DROPDOWN FUNCTIONS - مكتوبة بشكل عادي
window.toggleDropdown = function(id) {
    closeAllDropdowns();
    const element = document.getElementById(id);
    if (element) {
        element.style.display = 'block';
    }
}


document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers after DOM is fully loaded
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function() {
            const dropdownId = this.textContent.toLowerCase() + 'Dropdown';
            toggleDropdown(dropdownId);
        });
    });
});

function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-panel, .mega-menu');
    dropdowns.forEach(el => el.style.display = 'none');
}

document.addEventListener("click", function (e) {
    const isMenuItem = e.target.closest('.menu-item');
    const isDropdown = e.target.closest('.dropdown-panel, .mega-menu');

    if (!isMenuItem && !isDropdown) {
        closeAllDropdowns();
    }
});
