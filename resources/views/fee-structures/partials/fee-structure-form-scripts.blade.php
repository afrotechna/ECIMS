@push('scripts')
<script>
(function () {
    window.__feePresetPackages = @json(config('fee_structure_presets.packages'));

    function bindPicker(root) {
        var sel = root.querySelector('.fee-pick-select');
        var hidden = root.querySelector('.fee-pick-hidden');
        var custom = root.querySelector('.fee-pick-custom');
        if (!sel || !hidden) return;

        function apply() {
            if (sel.value === '__other__' && custom) {
                custom.classList.remove('d-none');
                hidden.value = custom.value || '0';
                custom.focus();
            } else {
                if (custom) custom.classList.add('d-none');
                hidden.value = sel.value;
            }
        }

        sel.addEventListener('change', apply);
        if (custom) {
            custom.addEventListener('input', function () {
                if (sel.value === '__other__') {
                    hidden.value = custom.value || '0';
                }
            });
        }
        apply();
    }

    function applyPreset(key) {
        var pkg = window.__feePresetPackages[key];
        if (!pkg) return;
        Object.keys(pkg).forEach(function (field) {
            if (field === 'label') return;
            var root = document.querySelector('.fee-amount-picker[data-field="' + field + '"]');
            if (!root) return;
            var amt = String(pkg[field]);
            var sel = root.querySelector('.fee-pick-select');
            var custom = root.querySelector('.fee-pick-custom');
            var hidden = root.querySelector('.fee-pick-hidden');
            if (!sel || !hidden) return;
            var opt = Array.prototype.some.call(sel.options, function (o) {
                return o.value === amt;
            });
            if (opt) {
                sel.value = amt;
            } else {
                sel.value = '__other__';
                if (custom) custom.value = amt;
            }
            sel.dispatchEvent(new Event('change'));
        });
    }

    function init() {
        document.querySelectorAll('.fee-amount-picker').forEach(bindPicker);
        var preset = document.getElementById('fee_preset_quickfill');
        if (preset) {
            preset.addEventListener('change', function () {
                if (!this.value) return;
                applyPreset(this.value);
                this.value = '';
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
