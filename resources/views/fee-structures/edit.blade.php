@extends('layouts.app')

@section('title', 'Edit fee schedule')

@section('content')
@php
    $s1 = $structure->feeStructureSemesters->firstWhere('semester_number', 1);
    $s2 = $structure->feeStructureSemesters->firstWhere('semester_number', 2);
    $fallbackSem1T = (int) round($structure->tuition * 595 / 920);
    $fallbackSem2C = (int) round($structure->tuition * 325 / 920);
    $fallbackSem2R = (int) round($structure->tuition * 595 / 920);
    $defSem1T = old('sem1_tuition', $s1 ? (int) $s1->tuition : $fallbackSem1T);
    $defSem1Nhif = old('sem1_nhif', $s1 ? (int) $s1->nhif : (int) $structure->nhif);
    $defSem1Qa = old('sem1_nactvet_qa', $s1 ? (int) $s1->nactvet_qa : (int) $structure->nactvet_qa);
    $defSem2C = old('sem2_tuition_continuous', $s2 ? (int) $s2->tuition : $fallbackSem2C);
    $defSem2R = old('sem2_tuition_repeat_transfer', $s2 && $s2->tuition_repeat_transfer !== null ? (int) $s2->tuition_repeat_transfer : $fallbackSem2R);
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('fee-structures.index') }}">Finance · Fees</a>
    <span class="mx-2">/</span>
    <span>Edit {{ $structure->academic_year }}{{ $structure->programme ? ' · ' . $structure->programme->code : '' }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit schedule</h1>
    <p class="page-subtitle-landing mb-0">{{ \App\Support\AcademicSession::label((int) $structure->academic_year) }} · {{ $structure->programme ? $structure->programme->code : 'All programmes' }}</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil me-2"></i>Details</div>
    <div class="card-body">
        <form action="{{ route('fee-structures.update', $structure) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="academic_year" class="form-label">Session <span class="text-danger">*</span></label>
                    <select class="form-select" id="academic_year" name="academic_year" required data-no-search>
                        @foreach($sessionYears as $start => $label)
                            <option value="{{ $start }}" {{ (int) old('academic_year', $structure->academic_year) === (int) $start ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Session start year.</small>
                </div>
                <div class="col-md-4">
                    <label for="programme_id" class="form-label">Programme</label>
                    <select class="form-select" id="programme_id" name="programme_id">
                        <option value="">All programmes</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ old('programme_id', $structure->programme_id) == $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Active</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $structure->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="fee_preset_quickfill" class="form-label"><i class="bi bi-lightning-charge me-1"></i>Quick fill</label>
                    <select id="fee_preset_quickfill" class="form-select">
                        <option value="">— Package —</option>
                        @foreach(config('fee_structure_presets.packages') as $key => $pkg)
                            <option value="{{ $key }}">{{ $pkg['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12"><hr></div>
                <div class="col-12"><h6 class="text-muted">Semester I</h6></div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_tuition',
                        'label' => 'Tuition (TZS)',
                        'required' => true,
                        'value' => $defSem1T,
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_nhif',
                        'label' => 'NHIF (TZS)',
                        'required' => false,
                        'value' => $defSem1Nhif,
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_nactvet_qa',
                        'label' => 'QA fee (TZS)',
                        'required' => false,
                        'value' => $defSem1Qa,
                    ])
                </div>
                <div class="col-12"><hr><h6 class="text-muted">Semester II</h6></div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem2_tuition_continuous',
                        'label' => 'Tuition — continuing (TZS)',
                        'required' => true,
                        'value' => $defSem2C,
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem2_tuition_repeat_transfer',
                        'label' => 'Tuition — repeat / transfer (TZS)',
                        'required' => true,
                        'value' => $defSem2R,
                    ])
                </div>
                <div class="col-12"><hr><h6 class="text-muted">Other (optional)</h6></div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'accommodation',
                        'label' => 'Accommodation (TZS)',
                        'required' => false,
                        'value' => old('accommodation', $structure->accommodation),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'other_charges',
                        'label' => 'Other (TZS)',
                        'required' => false,
                        'value' => old('other_charges', $structure->other_charges),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'national_exam_fee',
                        'label' => 'National exam fee (TZS)',
                        'required' => false,
                        'value' => old('national_exam_fee', $structure->national_exam_fee),
                    ])
                </div>
                <div class="col-12"><hr><h6 class="text-muted">Itemized breakdown (optional)</h6>
                    <p class="small text-muted">Informational only &mdash; shown on the fee schedule to explain what the Tuition / Other totals above are made of. Does not change the amounts actually billed.</p>
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_internal_exam',
                        'label' => 'Internal exams — Sem I (TZS)',
                        'required' => false,
                        'value' => old('sem1_internal_exam', $s1?->internal_exam ?? 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem2_internal_exam',
                        'label' => 'Internal exams — Sem II (TZS)',
                        'required' => false,
                        'value' => old('sem2_internal_exam', $s2?->internal_exam ?? 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'registration',
                        'label' => 'Registration (TZS)',
                        'required' => false,
                        'value' => old('registration', $s1?->registration ?? 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'games',
                        'label' => 'Games / sports (TZS)',
                        'required' => false,
                        'value' => old('games', $s1?->games ?? 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'emergency_fund',
                        'label' => 'Emergency fund (TZS)',
                        'required' => false,
                        'value' => old('emergency_fund', $s1?->emergency_fund ?? 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'practicum_guide',
                        'label' => 'Practicum guide (TZS)',
                        'required' => false,
                        'value' => old('practicum_guide', $s1?->practicum_guide ?? 0),
                    ])
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update</button>
                <a href="{{ route('fee-structures.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@include('fee-structures.partials.fee-structure-form-scripts')
@endsection
