/**
 * One-click "copy to clipboard" for things like issued temporary passwords.
 * navigator.clipboard requires a secure context (HTTPS or localhost) — this
 * app is sometimes reached over plain HTTP on a LAN IP, where that API is
 * unavailable, so we fall back to the older execCommand('copy') approach
 * (works over plain HTTP) whenever the modern API can't be used.
 */
window.cohasCopyText = function (text, btn) {
    var done = function (ok) {
        if (!btn) return;
        var original = btn.getAttribute('data-label-base') || btn.textContent;
        if (!btn.getAttribute('data-label-base')) btn.setAttribute('data-label-base', original);
        btn.textContent = ok ? 'Copied!' : 'Copy failed — select manually';
        setTimeout(function () { btn.textContent = btn.getAttribute('data-label-base'); }, 1800);
    };
    var fallback = function () {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(ta);
        done(ok);
    };
    if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function () { done(true); }, fallback);
    } else {
        fallback();
    }
};

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
            // Forms/selects/inputs can't safely be relocated into a popup (they'd lose
            // their submit context); a plain button with its own inline onclick (e.g.
            // a "copy to clipboard" button) survives the innerHTML move fine, so it's
            // not excluded here.
            if (el.querySelector('form, select, input')) return;

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
