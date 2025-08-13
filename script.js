// ========== SLIDER FUNCTIONALITY ==========
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

// ========== DROPDOWN MENU FUNCTIONALITY ==========
window.toggleDropdown = function (id) {
    window.closeAllDropdowns();

    const element = document.getElementById(id);
    const backdrop = document.getElementById("backdrop");

    if (element && backdrop) {
        element.style.display = 'block';
        backdrop.style.display = 'block';
    }
};

window.closeAllDropdowns = function () {
    const dropdowns = document.querySelectorAll('.dropdown-panel, .mega-menu');
    dropdowns.forEach(el => el.style.display = 'none');

    const backdrop = document.getElementById("backdrop");
    if (backdrop) {
        backdrop.style.display = 'none';
    }
};

document.addEventListener("click", function (e) {
    const isMenuItem = e.target.closest('.menu-item');
    const isDropdown = e.target.closest('.dropdown-panel, .mega-menu');

    if (!isMenuItem && !isDropdown) {
        closeAllDropdowns();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function () {
            const dropdownId = this.textContent.toLowerCase() + 'Dropdown';
            toggleDropdown(dropdownId);
        });
    });
});

