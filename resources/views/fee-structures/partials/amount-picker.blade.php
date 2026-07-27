{{-- Expects: $name, $label, $value, optional $required --}}
@php
    $required = $required ?? false;
    $allowOther = config('fee_structure_presets.allow_custom_amounts', false);
    $options = config('fee_structure_presets.pick.'.$name, []);
    $valueInt = (int) $value;
    $matched = false;
    foreach (array_keys($options) as $k) {
        if ((int) $k === $valueInt) {
            $matched = true;
            break;
        }
    }
    $singleFixed = count($options) === 1 && ! $allowOther;
@endphp
<div class="fee-amount-picker" data-field="{{ $name }}">
    <label class="form-label" for="{{ $name }}_pick">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @if(count($options) === 0)
        <input type="number" name="{{ $name }}" class="form-control" value="{{ $valueInt }}" min="0" step="1" @if($required) required @endif>
    @elseif($singleFixed)
        @php $onlyAmt = (int) array_key_first($options); $onlyLbl = $options[$onlyAmt] ?? number_format($onlyAmt); @endphp
        <div class="form-control bg-light border">{{ $onlyLbl }}</div>
        <input type="hidden" name="{{ $name }}" class="fee-pick-hidden" value="{{ $onlyAmt }}" @if($required) required @endif>
    @else
        <select id="{{ $name }}_pick" class="form-select fee-pick-select" aria-label="{{ $label }}">
            @foreach($options as $amt => $lbl)
                <option value="{{ $amt }}" @selected($matched && (int) $amt === $valueInt)>{{ $lbl }}</option>
            @endforeach
            @if($allowOther)
                <option value="__other__" @selected(! $matched)>Other amount…</option>
            @endif
        </select>
        @if($allowOther)
            <input type="number" id="{{ $name }}_custom" class="form-control mt-2 fee-pick-custom @if($matched) d-none @endif" min="0" step="1" value="{{ $valueInt }}" inputmode="numeric" aria-label="{{ $label }} other amount">
        @endif
        <input type="hidden" id="{{ $name }}_hidden" name="{{ $name }}" class="fee-pick-hidden" value="{{ $valueInt }}" @if($required) required @endif>
    @endif
</div>
