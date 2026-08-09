/**
 * Converts page-load notice banners (marked with [data-swal-notice]) into
 * SweetAlert popups instead of inline boxes. Multiple notices on the same
 * page fire one after another, not stacked. Elements are removed from the
 * DOM after being read so nothing is left behind once the popup is closed.
 */
(function () {
    function boot() {
        if (typeof Swal === 'undefined') return;

        var notices = Array.prototype.slice.call(document.querySelectorAll('[data-swal-notice]'));
        var queue = [];

        notices.forEach(function (el) {
            if (el.closest('.modal, .offcanvas, .swal2-container')) return;
            if (el.querySelector('form, select, input, button')) return;

            var icon = 'info';
            if (el.classList.contains('alert-danger')) icon = 'error';
            else if (el.classList.contains('alert-warning')) icon = 'warning';
            else if (el.classList.contains('alert-success')) icon = 'success';

            var html = el.innerHTML.trim();
            if (!html) return;

            queue.push({ icon: icon, html: html });
            el.remove();
        });

        if (!queue.length) return;

        var i = 0;
        function next() {
            if (i >= queue.length) return;
            var item = queue[i++];
            Swal.fire({ icon: item.icon, html: item.html, confirmButtonText: 'OK' }).then(next);
        }
        next();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
