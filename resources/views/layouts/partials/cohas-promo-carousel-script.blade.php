<script>
document.addEventListener('DOMContentLoaded', function () {
    var slides = document.querySelectorAll('.auth-promo-slide');
    var dots = document.querySelectorAll('.auth-carousel-dots button');
    if (!slides.length) {
        return;
    }
    var current = 0;
    var timer;

    function showSlide(n) {
        current = n;
        slides.forEach(function (s, i) { s.classList.toggle('active', i === n); });
        dots.forEach(function (d, i) { d.classList.toggle('active', i === n); });
    }

    function nextSlide() { showSlide((current + 1) % slides.length); }

    dots.forEach(function (btn) {
        btn.addEventListener('click', function () {
            showSlide(parseInt(btn.getAttribute('data-go'), 10));
            clearInterval(timer);
            timer = setInterval(nextSlide, 7000);
        });
    });

    if (slides.length > 1) {
        timer = setInterval(nextSlide, 7000);
    }
});
</script>
