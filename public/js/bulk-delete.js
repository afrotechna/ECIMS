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

    // For forms opted into data-bulk-group-by="nta-level": "23 CMT L4, 12 CMT L6"
    // instead of a flat total, built from each checked box's data-bulk-group-label
    // (falling back to data-nta-level alone if no programme label was rendered).
    function groupBreakdown(checkedCbs) {
        var counts = {};
        var order = [];
        checkedCbs.forEach(function (cb) {
            var label = cb.getAttribute('data-bulk-group-label');
            if (!label) {
                var lvl = cb.getAttribute('data-nta-level');
                if (!lvl) return;
                label = 'Level ' + lvl;
            }
            if (!(label in counts)) order.push(label);
            counts[label] = (counts[label] || 0) + 1;
        });
        if (!order.length) return '';
        order.sort();
        return order.map(function (label) { return counts[label] + ' ' + label; }).join(', ');
    }

    function updateBulkButton(form) {
        var btn = form.querySelector('.bulk-delete-submit');
        if (!btn) return;
        var scope = scopeForForm(form);
        var checked = checkboxesInScope(scope).filter(function (cb) { return cb.checked; });
        var n = checked.length;
        btn.disabled = n === 0;
        btn.classList.toggle('d-none', n === 0);
        var countEl = btn.querySelector('.bulk-delete-count');
        if (countEl) countEl.textContent = String(n);
        if (form.getAttribute('data-bulk-group-by') === 'nta-level') {
            if (!btn.getAttribute('data-label-base')) {
                btn.setAttribute('data-label-base', btn.getAttribute('title') || '');
            }
            var baseTitle = btn.getAttribute('data-label-base');
            var breakdown = groupBreakdown(checked);
            btn.title = breakdown ? baseTitle + ' — ' + breakdown : baseTitle;
        }
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
        e.preventDefault();
        var scope = scopeForForm(form);
        var checkedCbs = checkboxesInScope(scope).filter(function (cb) { return cb.checked; });
        var ids = checkedCbs.map(function (cb) { return cb.value; });
        if (ids.length === 0) {
            if (window.Swal) {
                Swal.fire({ icon: 'warning', title: 'Nothing selected', text: 'Select at least one item to delete.' });
            } else {
                alert('Select at least one item to delete.');
            }
            return;
        }
        var template = form.getAttribute('data-bulk-confirm') || 'Delete :count selected item(s)? This cannot be undone.';
        var msg = template.replace(':count', String(ids.length));
        if (form.getAttribute('data-bulk-group-by') === 'nta-level') {
            var breakdown = groupBreakdown(checkedCbs);
            if (breakdown) msg += '\n\n' + breakdown + '.';
        }
        var finish = function () {
            var holder = form.querySelector('.bulk-delete-ids');
            if (holder) {
                holder.innerHTML = '';
                ids.forEach(function (id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    holder.appendChild(input);
                });
            }
            HTMLFormElement.prototype.submit.call(form);
        };
        if (!window.Swal) {
            if (confirm(msg)) finish();
            return;
        }
        Swal.fire({
            title: 'Are you sure?',
            html: msg.replace(/\n/g, '<br>'),
            icon: form.getAttribute('data-bulk-confirm-icon') || 'warning',
            showCancelButton: true,
            confirmButtonText: form.getAttribute('data-bulk-confirm-button') || 'Delete',
            confirmButtonColor: form.getAttribute('data-bulk-confirm-color') || '#dc3545',
            cancelButtonColor: '#6c757d',
        }).then(function (result) {
            if (result.isConfirmed) finish();
        });
    });
})();
