(function () {
    function scopeForForm(form) {
        var tableId = form.getAttribute('data-bulk-table');
        if (tableId) {
            var table = document.getElementById(tableId);
            if (table) return table;
        }
        var scopeId = form.getAttribute('data-bulk-scope');
        if (scopeId) {
            var el = document.getElementById(scopeId);
            if (el) return el;
        }
        return form.closest('[data-bulk-delete-scope]');
    }

    function checkboxesInScope(scope) {
        if (!scope) return [];
        return Array.from(scope.querySelectorAll('.bulk-delete-cb'));
    }

    function updateBulkButton(form) {
        var btn = form.querySelector('.bulk-delete-submit');
        if (!btn) return;
        var scope = scopeForForm(form);
        var checked = checkboxesInScope(scope).filter(function (cb) { return cb.checked; });
        var n = checked.length;
        btn.disabled = n === 0;
        var base = btn.getAttribute('data-label-base') || 'Delete selected';
        btn.innerHTML = n > 0
            ? '<i class="bi bi-trash me-1"></i>' + base + ' (' + n + ')'
            : '<i class="bi bi-trash me-1"></i>' + base;
    }

    function syncSelectAll(scope) {
        if (!scope) return;
        var all = checkboxesInScope(scope);
        var checked = all.filter(function (cb) { return cb.checked; });
        scope.querySelectorAll('.bulk-delete-select-all').forEach(function (master) {
            master.checked = all.length > 0 && checked.length === all.length;
            master.indeterminate = checked.length > 0 && checked.length < all.length;
        });
    }

    document.querySelectorAll('.bulk-delete-form').forEach(function (form) {
        updateBulkButton(form);
    });

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (t.classList && t.classList.contains('bulk-delete-select-all')) {
            var tableId = t.getAttribute('data-table');
            var scopeId = t.getAttribute('data-bulk-scope');
            var scope = scopeId ? document.getElementById(scopeId)
                : (tableId ? document.getElementById(tableId) : t.closest('[data-bulk-delete-scope]'));
            if (!scope) return;
            checkboxesInScope(scope).forEach(function (cb) {
                cb.checked = t.checked;
            });
            document.querySelectorAll('.bulk-delete-form').forEach(function (form) {
                if (scopeForForm(form) === scope || (tableId && form.getAttribute('data-bulk-table') === tableId)) {
                    updateBulkButton(form);
                }
            });
            return;
        }
        if (t.classList && t.classList.contains('bulk-delete-cb')) {
            var scope = t.closest('table') || t.closest('[data-bulk-delete-scope]');
            syncSelectAll(scope);
            document.querySelectorAll('.bulk-delete-form').forEach(function (form) {
                var fs = scopeForForm(form);
                if (fs === scope || (scope && fs && fs.contains(t))) {
                    updateBulkButton(form);
                }
            });
        }
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.classList || !form.classList.contains('bulk-delete-form')) return;
        var scope = scopeForForm(form);
        var ids = checkboxesInScope(scope).filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
        if (ids.length === 0) {
            e.preventDefault();
            alert('Select at least one item to delete.');
            return;
        }
        var template = form.getAttribute('data-bulk-confirm') || 'Delete :count selected item(s)? This cannot be undone.';
        var msg = template.replace(':count', String(ids.length));
        if (!confirm(msg)) {
            e.preventDefault();
            return;
        }
        var holder = form.querySelector('.bulk-delete-ids');
        if (!holder) return;
        holder.innerHTML = '';
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            holder.appendChild(input);
        });
    });
})();
