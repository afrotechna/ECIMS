(function () {
    if (typeof TomSelect === 'undefined') return;

    var MIN_OPTIONS = 8;

    function enhance(select) {
        if (select.tomselect || select.hasAttribute('data-no-search') || select.multiple) return;
        if (select.options.length < MIN_OPTIONS) return;

        var sizeClass = select.classList.contains('form-select-sm') ? 'form-select-sm'
            : select.classList.contains('form-select-lg') ? 'form-select-lg' : null;

        var ts = new TomSelect(select, {
            create: false,
            allowEmptyOption: true,
            maxOptions: null,
            placeholder: select.getAttribute('data-placeholder') || 'Select…',
            plugins: select.hasAttribute('required') ? [] : ['clear_button'],
        });

        if (sizeClass) ts.wrapper.classList.add(sizeClass);
    }

    function enhanceAll(root) {
        (root || document).querySelectorAll('select').forEach(enhance);
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceAll(document);
    });

    // Re-scan when content is swapped in dynamically (modals, AJAX-loaded fragments).
    document.addEventListener('shown.bs.modal', function (e) {
        enhanceAll(e.target);
    });
})();
