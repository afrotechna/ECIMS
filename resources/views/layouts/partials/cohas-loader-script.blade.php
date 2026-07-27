<script>
(function () {
    function hideLoader() {
        var el = document.getElementById('cohas-page-loader');
        if (!el || el.classList.contains('is-hidden')) {
            return;
        }
        el.classList.add('is-hidden');
        setTimeout(function () {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 450);
    }

    if (document.readyState === 'complete') {
        requestAnimationFrame(hideLoader);
    } else {
        window.addEventListener('load', hideLoader);
        setTimeout(hideLoader, 8000);
    }
})();
</script>
