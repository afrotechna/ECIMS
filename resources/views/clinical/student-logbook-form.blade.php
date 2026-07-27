@extends('layouts.app')
@section('title', $entry->exists ? 'Edit logbook entry' : 'New logbook entry')
@section('content')
@php $isEdit = $entry->exists; @endphp
<nav class="student-breadcrumb">
    <a href="{{ route('my.clinical.logbook.index') }}">Clinical logbook</a>
    <span class="mx-2">/</span>
    <span>{{ $isEdit ? 'Edit' : 'New entry' }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing mb-0">{{ $isEdit ? 'Edit logbook entry' : 'New clinical logbook entry' }}</h1>
    <p class="page-subtitle-landing mb-0">Document a skill or procedure from the CMT NTA 4 practicum guide (no patient names — use case reference only).</p>
</div>

@include('clinical.partials.nta4-practicum-reference')

<div class="card card-landing">
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('my.clinical.logbook.update', $entry) : route('my.clinical.logbook.store') }}">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Procedure / skill <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select name="clinical_procedure_id" id="clinical_procedure_id" class="form-select @error('clinical_procedure_id') is-invalid @enderror" required>
                            <option value="">— Select procedure (whole checklist or module skill) —</option>
                            @foreach($procedure_select_groups ?? [] as $groupLabel => $groupProcedures)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($groupProcedures as $p)
                                <option value="{{ $p->id }}" {{ (int) old('clinical_procedure_id', $entry->clinical_procedure_id) === $p->id ? 'selected' : '' }}>
                                    {{ $p->code }} — {{ Str::limit($p->name, 70) }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary" id="logbookProcedureGuideBtn" title="What to do — open step guide" disabled>
                            <i class="bi bi-journal-text"></i>
                        </button>
                    </div>
                    <div class="form-text">Select a procedure, then use <i class="bi bi-journal-text"></i> to open the instruction guide.</div>
                    @error('clinical_procedure_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date performed <span class="text-danger">*</span></label>
                    <input type="date" name="performed_on" class="form-control @error('performed_on') is-invalid @enderror" value="{{ old('performed_on', $entry->performed_on?->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                    @error('performed_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <select name="department_code" class="form-select">
                        <option value="">—</option>
                        @foreach($departmentLabels as $code => $label)
                            <option value="{{ $code }}" {{ old('department_code', $entry->department_code) === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Case reference (optional)</label>
                    <input type="text" name="case_reference" class="form-control" value="{{ old('case_reference', $entry->case_reference) }}" placeholder="e.g. Ward log #12 — no names">
                </div>
                <div class="col-12">
                    <label class="form-label">Case summary <span class="text-danger">*</span></label>
                    <textarea name="case_summary" class="form-control @error('case_summary') is-invalid @enderror" rows="4" required placeholder="Brief anonymized description of the patient presentation and your role">{{ old('case_summary', $entry->case_summary) }}</textarea>
                    @error('case_summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Skills / procedures performed</label>
                    <textarea name="skills_notes" class="form-control" rows="3" placeholder="What you did under supervision">{{ old('skills_notes', $entry->skills_notes) }}</textarea>
                </div>
            </div>

            @if($placement)
                <p class="small text-muted mt-3 mb-0">Linked placement: <strong>{{ $placement->name }}</strong> — {{ \App\Support\ClinicalRotationCatalog::hospitalLabel($placement->hospital_code) }}</p>
            @endif

            <hr class="my-4">
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="submit" value="0" class="btn btn-outline-secondary">Save draft</button>
                <button type="submit" name="submit" value="1" class="btn btn-primary">Submit for instructor review</button>
                <a href="{{ route('my.clinical.logbook.index') }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>

@if(!empty($procedure_guides))
@push('scripts')
<script>
(function () {
    const select = document.getElementById('clinical_procedure_id');
    const btn = document.getElementById('logbookProcedureGuideBtn');
    if (!select || !btn) return;

    function syncGuideBtn() {
        btn.disabled = !select.value;
    }
    select.addEventListener('change', syncGuideBtn);
    btn.addEventListener('click', function () {
        if (select.value && typeof window.openClinicalProcedureGuide === 'function') {
            window.openClinicalProcedureGuide(select.value);
        }
    });
    syncGuideBtn();
})();
</script>
@endpush
@endif
@endsection
