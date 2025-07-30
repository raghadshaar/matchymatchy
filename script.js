let current = 0;
const slides = document.querySelectorAll('.slide');
const total = slides.length;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (i === index) {
            slide.classList.add('active');
        }
    });
}

function nextSlide() {
    current = (current + 1) % total;
    showSlide(current);
}

function prevSlide() {
    current = (current - 1 + total) % total;
    showSlide(current);
}

// Auto Slide every 5 seconds
setInterval(nextSlide, 5000);
