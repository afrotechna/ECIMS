@php
    $selected = old('submitted_certificates', $student->submitted_certificates ?? []);
    if (! is_array($selected)) {
        $selected = [];
    }
@endphp
<div class="col-12">
    <label class="form-label d-block">Certificates submitted <span class="text-muted small">(select all that apply)</span></label>
    <div class="row g-2">
        @foreach(\App\Models\Student::CERTIFICATE_OPTIONS as $key => $label)
        <div class="col-md-4">
            <div class="form-check border rounded px-3 py-2 h-100 bg-white">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="submitted_certificates[]"
                    value="{{ $key }}"
                    id="cert_{{ $key }}"
                    {{ in_array($key, $selected, true) ? 'checked' : '' }}
                >
                <label class="form-check-label small" for="cert_{{ $key }}">{{ $label }}</label>
            </div>
        </div>
        @endforeach
    </div>
    @error('submitted_certificates')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @error('submitted_certificates.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
<div class="col-md-12">
    <label class="form-label">Other notes <span class="text-muted small">(optional)</span></label>
    <input type="text" name="academic_requirements" class="form-control @error('academic_requirements') is-invalid @enderror" value="{{ old('academic_requirements', $student->academic_requirements) }}" placeholder="Any other registry note (not gloves / ream)">
    @error('academic_requirements')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
