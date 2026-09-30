(function (global) {
    function resetDefaults() {
        if (global.CohasTheme) global.CohasTheme.apply('light');
    }

    function syncControls() {
        if (global.CohasTheme) {
            var theme = global.CohasTheme.current();
            document.querySelectorAll('[data-customizer-theme]').forEach(function (el) {
                el.classList.toggle('is-active', el.getAttribute('data-customizer-theme') === theme);
            });
        }
    }

    global.CohasCustomizer = {
        resetDefaults: resetDefaults,
        syncControls: syncControls,
    };

    document.addEventListener('DOMContentLoaded', function () {
        // One-time cleanup: the "boxed" layout-width option was removed (it was leaving big
        // unwanted side gutters on every page) — clear any stale value from earlier so a device
        // that had it selected doesn't carry dead state around.
        try { global.localStorage.removeItem('cohas-layout-width'); } catch (e) {}

        document.querySelectorAll('[data-customizer-theme]').forEach(function (el) {
            el.addEventListener('click', function () {
                if (global.CohasTheme) global.CohasTheme.apply(el.getAttribute('data-customizer-theme'));
                syncControls();
            });
        });
        var resetBtn = document.getElementById('customizerReset');
        if (resetBtn) resetBtn.addEventListener('click', resetDefaults);

        global.addEventListener('cohas-theme-change', syncControls);

        var panel = document.getElementById('customizerPanel');
        if (panel) panel.addEventListener('show.bs.offcanvas', syncControls);

        syncControls();
    });
})(window);
