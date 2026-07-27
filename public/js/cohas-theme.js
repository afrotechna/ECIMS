(function (global) {
    var STORAGE_KEY = 'cohas-theme';
    var THEMES = ['light', 'dark'];

    function normalize(theme) {
        return THEMES.indexOf(theme) !== -1 ? theme : 'light';
    }

    function preferred() {
        if (global.matchMedia && global.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function stored() {
        try {
            return global.localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function current() {
        return normalize(stored() || preferred());
    }

    function apply(theme) {
        theme = normalize(theme);
        var root = document.documentElement;
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        try {
            global.localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {}
        global.dispatchEvent(new CustomEvent('cohas-theme-change', { detail: { theme: theme } }));
    }

    function toggle() {
        apply(current() === 'dark' ? 'light' : 'dark');
        syncToggleButtons();
    }

    function syncToggleButtons() {
        var theme = current();
        document.querySelectorAll('[data-cohas-theme-toggle]').forEach(function (btn) {
            var isDark = theme === 'dark';
            btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            btn.setAttribute('title', btn.getAttribute(isDark ? 'data-title-light' : 'data-title-dark') || '');
            var icon = btn.querySelector('[data-theme-icon]');
            if (icon) {
                icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        });
    }

    global.CohasTheme = {
        apply: apply,
        toggle: toggle,
        current: current,
        syncToggleButtons: syncToggleButtons,
    };

    apply(current());

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-cohas-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                toggle();
            });
        });
        syncToggleButtons();
    });
})(window);
