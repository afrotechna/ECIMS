@extends('layouts.app')

@section('title', 'Edit Course')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('courses.index') }}">Module catalogue</a>
    <span class="mx-2">/</span>
    <span>Edit {{ $course->code }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit Course / Module</h1>
    <p class="page-subtitle-landing mb-0">{{ $course->code }} — {{ $course->name }}</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil me-2"></i>Course details</div>
    <div class="card-body">
        <form id="courseEditForm" action="{{ route('courses.update', $course) }}" method="POST">
            @csrf
            @method('PUT')
            @if(! empty($returnSemesterId))
                <input type="hidden" name="return_semester_id" value="{{ $returnSemesterId }}">
            @endif
            @if(! empty($returnProgrammeId))
                <input type="hidden" name="return_programme_id" value="{{ $returnProgrammeId }}">
            @endif
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $course->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-9">
                    <label class="form-label">Course / Module name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $course->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Programme <span class="text-danger">*</span></label>
                    <select class="form-select @error('programme_id') is-invalid @enderror" name="programme_id" required>
                        @foreach($programmes as $p)
                        <option value="{{ $p->id }}" {{ old('programme_id', $course->programme_id) == $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('programme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year of study</label>
                    <select class="form-select" name="year_of_study" id="year_of_study">
                        @for($y = 1; $y <= 6; $y++)
                        <option value="{{ $y }}" {{ old('year_of_study', $course->year_of_study) == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">NTA Level <span class="text-danger">*</span></label>
                    <select class="form-select @error('nta_level') is-invalid @enderror" name="nta_level" id="nta_level" required>
                        @php $defaultNta = old('nta_level', $course->nta_level ?? $course->resolvedNtaLevel()); @endphp
                        @foreach([4 => 'NTA Level 4', 5 => 'NTA Level 5', 6 => 'NTA Level 6'] as $nv => $label)
                        <option value="{{ $nv }}" {{ (int) $defaultNta === $nv ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('nta_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Credits</label>
                    <input type="number" class="form-control" name="credits" value="{{ old('credits', $course->credits) }}" min="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">CA %</label>
                    <input type="number" class="form-control" name="ca_weight" value="{{ old('ca_weight', $course->ca_weight) }}" min="0" max="100" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">SE %</label>
                    <input type="number" class="form-control" name="exam_weight" value="{{ old('exam_weight', $course->exam_weight) }}" min="0" max="100" required>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_practical" value="1" id="has_practical" {{ old('has_practical', $course->has_practical ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_practical">Has practical / skills assessment (counts in CA average)</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="requires_clinical_rotation" value="1" id="requires_clinical_rotation" {{ old('requires_clinical_rotation', $course->requires_clinical_rotation ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="requires_clinical_rotation">Requires clinical rotation (hospital posting)</label>
                    </div>
                    <p class="small text-muted mb-0">Untick for classroom-only modules (e.g. pathology). Students who only repeat such modules are not placed in rotation groups.</p>
                </div>
                <div class="col-md-6" id="practicalTypeWrap">
                    <label class="form-label">Skills assessment type</label>
                    <select name="practical_assessment_type" class="form-select">
                        <option value="practical" {{ old('practical_assessment_type', $course->practical_assessment_type ?? 'practical') === 'practical' ? 'selected' : '' }}>Practical</option>
                        <option value="ospe" {{ old('practical_assessment_type', $course->practical_assessment_type) === 'ospe' ? 'selected' : '' }}>OSPE</option>
                        <option value="osce" {{ old('practical_assessment_type', $course->practical_assessment_type) === 'osce' ? 'selected' : '' }}>OSCE</option>
                    </select>
                    <p class="small text-muted mb-0">Shown on results entry and transcript when “Has practical” is ticked.</p>
                </div>
                <div class="col-12">
                    <label class="form-label">Offer in semesters</label>
                    @if($semesters->isNotEmpty())
                        <input type="hidden" name="semester_ids_submitted" value="1">
                    @endif
                    <div class="row g-2">
                        @php
                            $checked = array_map('intval', old('semester_ids', $course->semesters->pluck('id')->map(fn ($id) => (int) $id)->toArray()));
                        @endphp
                        @foreach($semesters as $s)
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="semester_ids[]" value="{{ $s->id }}" id="s{{ $s->id }}" {{ in_array((int) $s->id, $checked, true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="s{{ $s->id }}">{{ $s->label }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
        @canModule('courses', 'delete')
        <form id="courseDeleteForm" action="{{ route('courses.destroy', $course) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
        @endcanModule
        <hr class="my-4">
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" form="courseEditForm" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update</button>
            @canModule('courses', 'delete')
            <button type="button" form="courseDeleteForm" class="btn btn-outline-danger" data-swal-confirm data-swal-title="Delete this course?" data-swal-text="This cannot be undone." data-swal-icon="warning"><i class="bi bi-trash3 me-1"></i> Delete</button>
            @endcanModule
            <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Cancel</a>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cb = document.getElementById('has_practical');
    var wrap = document.getElementById('practicalTypeWrap');
    function sync() { if (wrap) wrap.style.display = cb && cb.checked ? 'block' : 'none'; }
    if (cb) { cb.addEventListener('change', sync); sync(); }
    var yearEl = document.getElementById('year_of_study');
    var ntaEl = document.getElementById('nta_level');
    function ntaFromYear(y) {
        y = parseInt(y, 10) || 1;
        return Math.min(6, Math.max(4, y + 3));
    }
    if (yearEl && ntaEl) {
        yearEl.addEventListener('change', function() {
            ntaEl.value = String(ntaFromYear(yearEl.value));
        });
    }
});
</script>
@endpush
@endsection
