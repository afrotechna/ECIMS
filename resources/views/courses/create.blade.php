@extends('layouts.app')

@section('title', 'Add Course')

@section('content')
@push('styles')
<style>
    .curriculum-matrix thead th { font-size: 0.78rem; vertical-align: middle; white-space: nowrap; }
    .curriculum-matrix tbody td { font-size: 0.8rem; }
    .curriculum-matrix tbody tr:hover { background: #f8fafc; }
    .curriculum-table-card { border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1rem 1.25rem; background: #fff; }
    .curriculum-fold-trigger { cursor: pointer; user-select: none; }
    .curriculum-fold-icon { transition: transform 0.2s ease; display: inline-block; }
    .curriculum-fold-trigger.collapsed .curriculum-fold-icon { transform: rotate(-90deg); }
</style>
@endpush

<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('courses.index') }}">Module catalogue</a>
    <span class="mx-2">/</span>
    <span>Add course</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add Course / Module</h1>
</div>

<script type="application/json" id="curriculum-modules-json">{!! json_encode(config('curriculum_modules')) !!}</script>

<form action="{{ route('courses.store') }}" method="POST" id="courseCreateForm">
    @csrf
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @error('curriculum_modules')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
    <input type="hidden" name="code" id="field_code" value="{{ old('code') }}">
    <input type="hidden" name="name" id="field_name" value="{{ old('name') }}">
    <input type="hidden" name="ca_weight" id="field_ca_weight" value="{{ old('ca_weight', 40) }}">
    <input type="hidden" name="exam_weight" id="field_exam_weight" value="{{ old('exam_weight', 60) }}">

    <div class="card card-landing mb-3">
        <div class="card-header-landing"><i class="bi bi-sliders me-2"></i>Programme &amp; level</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="programme_id" class="form-label">Programme <span class="text-danger">*</span></label>
                    <select class="form-select @error('programme_id') is-invalid @enderror" name="programme_id" id="programme_id" required>
                        <option value="">Select</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" data-programme-code="{{ strtoupper($p->code) }}" {{ (string) old('programme_id', $selectedProgrammeId ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                    @error('programme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3" id="ntaLevelWrap">
                    <label for="nta_level" class="form-label">NTA Level <span class="text-danger">*</span></label>
                    <select class="form-select @error('nta_level') is-invalid @enderror" name="nta_level" id="nta_level" required>
                        @foreach([4 => 'NTA Level 4', 5 => 'NTA Level 5', 6 => 'NTA Level 6'] as $nv => $label)
                            <option value="{{ $nv }}" {{ (int) old('nta_level', $selectedNtaLevel ?? 4) === $nv ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('nta_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="year_of_study" class="form-label">Year of study</label>
                    <select class="form-select" name="year_of_study" id="year_of_study">
                        @for($y = 1; $y <= 6; $y++)
                            <option value="{{ $y }}" {{ old('year_of_study', 1) == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3" id="creditsWrap">
                    <label for="credits" class="form-label">Credits</label>
                    <input type="number" class="form-control" name="credits" id="credits" value="{{ old('credits', 0) }}" min="0" step="0.01">
                </div>
            </div>
        </div>
    </div>

    <div id="curriculumTablesMount" class="d-none mb-3"></div>

    <div id="curriculumNotes" class="card card-landing mb-3 border-0 bg-light d-none">
        <div class="card-body small text-muted">
            <strong class="text-dark d-block mb-1">Key</strong>
            WR = Written · AS = Assignment · CLN/PR = Clinical/Practical · OSCE/OSPE = Objective Structured Clinical/Practical Exam · SE = Semester exam (60%) · Each module totals 100%.
        </div>
    </div>

    <div id="curriculumSelectedSummary" class="mb-3 d-none"></div>

    <div id="manualModuleFields" class="card card-landing mb-3">
        <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Module code &amp; name</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="manual_code" class="form-label">Code</label>
                    <input type="text" class="form-control" id="manual_code" value="{{ old('code') }}" autocomplete="off">
                </div>
                <div class="col-md-9">
                    <label for="manual_name" class="form-label">Course / Module name</label>
                    <input type="text" class="form-control" id="manual_name" value="{{ old('name') }}" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label for="manual_ca" class="form-label">CA %</label>
                    <input type="number" class="form-control" id="manual_ca" value="{{ old('ca_weight', 40) }}" min="0" max="100">
                </div>
                <div class="col-md-4">
                    <label for="manual_se" class="form-label">SE %</label>
                    <input type="number" class="form-control" id="manual_se" value="{{ old('exam_weight', 60) }}" min="0" max="100">
                </div>
            </div>
        </div>
    </div>

    <div class="card card-landing mb-3" id="assessmentOptionsCard">
        <div class="card-header-landing"><i class="bi bi-clipboard-data me-2"></i>Assessment options</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_practical" value="1" id="field_has_practical" {{ old('has_practical') ? 'checked' : '' }}>
                        <label class="form-check-label" for="field_has_practical">Has practical / skills assessment (CA)</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="requires_clinical_rotation" value="1" id="field_requires_clinical_rotation" {{ old('requires_clinical_rotation') ? 'checked' : '' }}>
                        <label class="form-check-label" for="field_requires_clinical_rotation">Requires clinical rotation (hospital posting)</label>
                    </div>
                </div>
                <div class="col-md-6" id="practicalTypeWrap">
                    <label class="form-label">Skills assessment type</label>
                    <select name="practical_assessment_type" class="form-select" id="field_practical_assessment_type">
                        <option value="practical" {{ old('practical_assessment_type', 'practical') === 'practical' ? 'selected' : '' }}>Practical</option>
                        <option value="ospe" {{ old('practical_assessment_type') === 'ospe' ? 'selected' : '' }}>OSPE</option>
                        <option value="osce" {{ old('practical_assessment_type') === 'osce' ? 'selected' : '' }}>OSCE</option>
                    </select>
                </div>
                <div class="col-12" id="semesterCheckboxesWrap">
                    <label class="form-label">Offer in semesters</label>
                    <div class="row g-2">
                        @foreach($semesters as $s)
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="semester_ids[]" value="{{ $s->id }}" id="s{{ $s->id }}" {{ in_array($s->id, old('semester_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="s{{ $s->id }}">{{ $s->label }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($semesters->isEmpty())
                    <p class="text-muted small mb-0">No semesters defined.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Course</button>
        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@include('courses.partials.curriculum-scripts')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cb = document.getElementById('field_has_practical');
    var wrap = document.getElementById('practicalTypeWrap');
    function sync() { if (wrap) wrap.style.display = cb && cb.checked ? 'block' : 'none'; }
    if (cb) { cb.addEventListener('change', sync); sync(); }

    var mc = document.getElementById('manual_code');
    var mn = document.getElementById('manual_name');
    var mca = document.getElementById('manual_ca');
    var mse = document.getElementById('manual_se');
    var fc = document.getElementById('field_code');
    var fn = document.getElementById('field_name');
    var fca = document.getElementById('field_ca_weight');
    var fex = document.getElementById('field_exam_weight');
    function syncManual() {
        if (!fc || !mc) return;
        var jsonEl = document.getElementById('curriculum-modules-json');
        var CUR = {}; try { CUR = JSON.parse(jsonEl.textContent || '{}'); } catch (e) {}
        var opt = document.getElementById('programme_id');
        var code = (opt && opt.options[opt.selectedIndex]) ? (opt.options[opt.selectedIndex].getAttribute('data-programme-code') || '').toUpperCase() : '';
        var nta = parseInt(document.getElementById('nta_level').value, 10) || 4;
        var hasPkg = CUR.programmes && CUR.programmes[code] && CUR.programmes[code][nta];
        if (hasPkg) return;
        fc.value = mc.value;
        fn.value = mn.value;
        if (fca && mca) fca.value = mca.value;
        if (fex && mse) fex.value = mse.value;
    }
    if (mc) mc.addEventListener('input', syncManual);
    if (mn) mn.addEventListener('input', syncManual);
    if (mca) mca.addEventListener('input', syncManual);
    if (mse) mse.addEventListener('input', syncManual);

    var form = document.getElementById('courseCreateForm');
    if (form) {
        form.addEventListener('submit', function() {
            var jsonEl = document.getElementById('curriculum-modules-json');
            var CUR = {}; try { CUR = JSON.parse(jsonEl.textContent || '{}'); } catch (e) {}
            var opt = document.getElementById('programme_id');
            var pcode = (opt && opt.options[opt.selectedIndex]) ? (opt.options[opt.selectedIndex].getAttribute('data-programme-code') || '').toUpperCase() : '';
            var nta = parseInt(document.getElementById('nta_level').value, 10) || 4;
            var hasPkg = CUR.programmes && CUR.programmes[pcode] && CUR.programmes[pcode][nta];
            if (!hasPkg) syncManual();
        });
    }

    var yearEl = document.getElementById('year_of_study');
    var ntaEl = document.getElementById('nta_level');
    function ntaFromYear(y) {
        y = parseInt(y, 10) || 1;
        return Math.min(6, Math.max(4, y + 3));
    }
    if (yearEl && ntaEl) {
        yearEl.addEventListener('change', function() {
            ntaEl.value = String(ntaFromYear(yearEl.value));
            ntaEl.dispatchEvent(new Event('change'));
        });
    }
});
</script>
@endpush
@endsection
