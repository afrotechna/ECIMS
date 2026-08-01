@php
    $idPrefix = $idPrefix ?? 'guardian_relationship';
    $isRequired = $required ?? false;
    $currentValue = $currentValue ?? null;
    $isOtherValue = $currentValue && ! in_array($currentValue, \App\Models\Student::GUARDIAN_RELATIONSHIPS, true);
@endphp
<select
    id="{{ $idPrefix }}_choice"
    class="form-select @error('guardian_relationship') is-invalid @enderror"
    @if($isRequired) required @endif
>
    <option value="">— Select relationship —</option>
    @foreach(\App\Models\Student::GUARDIAN_RELATIONSHIPS as $option)
        @continue($option === 'Other')
        <option value="{{ $option }}" {{ $currentValue === $option ? 'selected' : '' }}>{{ $option }}</option>
    @endforeach
    <option value="__other__" {{ $isOtherValue ? 'selected' : '' }}>Other</option>
</select>
<input
    type="text"
    id="{{ $idPrefix }}_other_text"
    class="form-control mt-2 {{ $isOtherValue ? '' : 'd-none' }}"
    placeholder="Specify relationship"
    value="{{ $isOtherValue ? $currentValue : '' }}"
>
<input type="hidden" name="guardian_relationship" id="{{ $idPrefix }}_hidden" value="{{ $currentValue }}">
@error('guardian_relationship')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

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
    }

    choice.addEventListener('change', sync);
    otherText.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
