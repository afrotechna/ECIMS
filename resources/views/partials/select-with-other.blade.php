@php
    $isRequired = $required ?? false;
    $currentValue = $currentValue ?? null;
    $placeholder = $placeholder ?? '— Select —';
    $otherPlaceholder = $otherPlaceholder ?? 'Please specify';
    $isOtherValue = $currentValue !== null && $currentValue !== '' && ! array_key_exists($currentValue, $options);
@endphp
<select
    id="{{ $idPrefix }}_choice"
    class="form-select @error($name) is-invalid @enderror"
    @if($isRequired) required @endif
>
    <option value="">{{ $placeholder }}</option>
    @foreach($options as $value => $label)
        <option value="{{ $value }}" {{ ! $isOtherValue && $currentValue === $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
    <option value="__other__" {{ $isOtherValue ? 'selected' : '' }}>Other</option>
</select>
<input
    type="text"
    id="{{ $idPrefix }}_other_text"
    class="form-control mt-2 {{ $isOtherValue ? '' : 'd-none' }}"
    placeholder="{{ $otherPlaceholder }}"
    value="{{ $isOtherValue ? $currentValue : '' }}"
>
<input type="hidden" name="{{ $name }}" id="{{ $idPrefix }}_hidden" value="{{ $currentValue }}">
@error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

@push('scripts')
<script>
(function () {
    var choice = document.getElementById('{{ $idPrefix }}_choice');
    var otherText = document.getElementById('{{ $idPrefix }}_other_text');
    var hidden = document.getElementById('{{ $idPrefix }}_hidden');
    if (!choice || !otherText || !hidden) return;

    function sync() {
        if (choice.value === '__other__') {
            otherText.classList.remove('d-none');
            hidden.value = otherText.value;
        } else {
            otherText.classList.add('d-none');
            hidden.value = choice.value;
        }
        hidden.dispatchEvent(new Event('change'));
    }

    choice.addEventListener('change', sync);
    otherText.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
