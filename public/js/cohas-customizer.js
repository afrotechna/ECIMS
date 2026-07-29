(function (global) {
    var MENU_SKIN_KEY = 'cohas-menu-skin';
    var LAYOUT_WIDTH_KEY = 'cohas-layout-width';
    var SIDEBAR_COLLAPSED_KEY = 'sidebarCollapsed';

    function getStorage(key, fallback) {
        try {
            return global.localStorage.getItem(key) || fallback;
        } catch (e) {
            return fallback;
        }
    }

    function setStorage(key, value) {
        try {
            global.localStorage.setItem(key, value);
        } catch (e) {}
    }

    function applyMenuSkin(skin) {
        skin = skin === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-menu-skin', skin);
        setStorage(MENU_SKIN_KEY, skin);
        syncControls();
    }

    function applyLayoutWidth(width) {
        width = width === 'boxed' ? 'boxed' : 'full';
        document.documentElement.setAttribute('data-layout-width', width);
        setStorage(LAYOUT_WIDTH_KEY, width);
        syncControls();
    }

    function applySidebarCollapsed(collapsed) {
        var sidebar = document.getElementById('sidebar');
        var mainWrap = document.getElementById('mainWrap');
        var icon = document.getElementById('sidebarToggleIcon');
        if (sidebar && mainWrap) {
            sidebar.classList.toggle('collapsed', collapsed);
            mainWrap.classList.toggle('collapsed', collapsed);
            mainWrap.classList.toggle('expanded', !collapsed);
            if (icon) icon.className = collapsed ? 'bi bi-layout-sidebar-inset' : 'bi bi-layout-sidebar-inset-reverse';
        }
        setStorage(SIDEBAR_COLLAPSED_KEY, collapsed ? '1' : '0');
        syncControls();
    }

    function resetDefaults() {
        applyMenuSkin('light');
        applyLayoutWidth('full');
        applySidebarCollapsed(false);
        if (global.CohasTheme) global.CohasTheme.apply('light');
    }

    function syncControls() {
        var menuSkin = getStorage(MENU_SKIN_KEY, 'light');
        var layoutWidth = getStorage(LAYOUT_WIDTH_KEY, 'full');
        var sidebarCollapsed = getStorage(SIDEBAR_COLLAPSED_KEY, '0') === '1';

        document.querySelectorAll('[data-customizer-menu-skin]').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-customizer-menu-skin') === menuSkin);
        });
        document.querySelectorAll('[data-customizer-layout-width]').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-customizer-layout-width') === layoutWidth);
        });
        var collapseSwitch = document.getElementById('customizerSidebarCollapsed');
        if (collapseSwitch) collapseSwitch.checked = sidebarCollapsed;
        if (global.CohasTheme) {
            var theme = global.CohasTheme.current();
            document.querySelectorAll('[data-customizer-theme]').forEach(function (el) {
                el.classList.toggle('is-active', el.getAttribute('data-customizer-theme') === theme);
            });
        }
    }

    global.CohasCustomizer = {
        applyMenuSkin: applyMenuSkin,
        applyLayoutWidth: applyLayoutWidth,
        applySidebarCollapsed: applySidebarCollapsed,
        resetDefaults: resetDefaults,
        syncControls: syncControls,
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-customizer-menu-skin]').forEach(function (el) {
            el.addEventListener('click', function () { applyMenuSkin(el.getAttribute('data-customizer-menu-skin')); });
        });
        document.querySelectorAll('[data-customizer-layout-width]').forEach(function (el) {
            el.addEventListener('click', function () { applyLayoutWidth(el.getAttribute('data-customizer-layout-width')); });
        });
        document.querySelectorAll('[data-customizer-theme]').forEach(function (el) {
            el.addEventListener('click', function () {
                if (global.CohasTheme) global.CohasTheme.apply(el.getAttribute('data-customizer-theme'));
                syncControls();
            });
        });
        var collapseSwitch = document.getElementById('customizerSidebarCollapsed');
        if (collapseSwitch) {
            collapseSwitch.addEventListener('change', function () { applySidebarCollapsed(collapseSwitch.checked); });
        }
        var resetBtn = document.getElementById('customizerReset');
        if (resetBtn) resetBtn.addEventListener('click', resetDefaults);

        global.addEventListener('cohas-theme-change', syncControls);

        var panel = document.getElementById('customizerPanel');
        if (panel) panel.addEventListener('show.bs.offcanvas', syncControls);

        syncControls();
    });
})(window);
