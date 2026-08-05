@extends('layouts.app')

@section('title', 'New fee schedule')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('fee-structures.index') }}">Finance · Fees</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>New schedule</h1>
    <p class="page-subtitle-landing mb-0">Choose amounts from the lists or use Quick fill.</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Details</div>
    <div class="card-body">
        <form action="{{ route('fee-structures.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="academic_year" class="form-label">Session <span class="text-danger">*</span></label>
                    <select class="form-select @error('academic_year') is-invalid @enderror" id="academic_year" name="academic_year" required data-no-search>
                        @foreach($sessionYears as $start => $label)
                            <option value="{{ $start }}" {{ (int) old('academic_year', \App\Support\AcademicSession::defaultStartYear()) === (int) $start ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Start year (e.g. 2025 → 2025/26).</small>
                </div>
                <div class="col-md-4">
                    <label for="programme_id" class="form-label">Programme</label>
                    <select class="form-select" id="programme_id" name="programme_id">
                        <option value="">All programmes</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ old('programme_id') == $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Active</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
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
            </div>

            <hr class="my-4">
            <h6 class="text-muted mb-2">Semester I</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_tuition',
                        'label' => 'Tuition (TZS)',
                        'required' => true,
                        'value' => old('sem1_tuition', 595000),
                    ])
                    @error('sem1_tuition')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_nhif',
                        'label' => 'NHIF (TZS)',
                        'required' => false,
                        'value' => old('sem1_nhif', 50400),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_nactvet_qa',
                        'label' => 'QA fee (TZS)',
                        'required' => false,
                        'value' => old('sem1_nactvet_qa', 20000),
                    ])
                </div>
            </div>

            <hr class="my-4">
            <h6 class="text-muted mb-2">Semester II</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem2_tuition_continuous',
                        'label' => 'Tuition — continuing (TZS)',
                        'required' => true,
                        'value' => old('sem2_tuition_continuous', 325000),
                    ])
                    @error('sem2_tuition_continuous')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem2_tuition_repeat_transfer',
                        'label' => 'Tuition — repeat / transfer (TZS)',
                        'required' => true,
                        'value' => old('sem2_tuition_repeat_transfer', 595000),
                    ])
                    @error('sem2_tuition_repeat_transfer')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">
            <h6 class="text-muted mb-2">Other (optional)</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'accommodation',
                        'label' => 'Accommodation (TZS)',
                        'required' => false,
                        'value' => old('accommodation', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'other_charges',
                        'label' => 'Other (TZS)',
                        'required' => false,
                        'value' => old('other_charges', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'national_exam_fee',
                        'label' => 'National exam fee (TZS)',
                        'required' => false,
                        'value' => old('national_exam_fee', 0),
                    ])
                </div>
            </div>

            <hr class="my-4">
            <h6 class="text-muted mb-2">Itemized breakdown (optional)</h6>
            <p class="small text-muted">Informational only &mdash; shown on the fee schedule to explain what the Tuition / Other totals above are made of. Does not change the amounts actually billed.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_internal_exam',
                        'label' => 'Internal exams — Sem I (TZS)',
                        'required' => false,
                        'value' => old('sem1_internal_exam', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem2_internal_exam',
                        'label' => 'Internal exams — Sem II (TZS)',
                        'required' => false,
                        'value' => old('sem2_internal_exam', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'registration',
                        'label' => 'Registration (TZS)',
                        'required' => false,
                        'value' => old('registration', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'games',
                        'label' => 'Games / sports (TZS)',
                        'required' => false,
                        'value' => old('games', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'emergency_fund',
                        'label' => 'Emergency fund (TZS)',
                        'required' => false,
                        'value' => old('emergency_fund', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'practicum_guide',
                        'label' => 'Practicum guide (TZS)',
                        'required' => false,
                        'value' => old('practicum_guide', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_national_exam',
                        'label' => 'National exam fee — Sem I (TZS)',
                        'required' => false,
                        'value' => old('sem1_national_exam', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem1_accommodation',
                        'label' => 'Accommodation — Sem I (TZS)',
                        'required' => false,
                        'value' => old('sem1_accommodation', 0),
                    ])
                </div>
                <div class="col-md-4">
                    @include('fee-structures.partials.amount-picker', [
                        'name' => 'sem2_accommodation',
                        'label' => 'Accommodation — Sem II (TZS)',
                        'required' => false,
                        'value' => old('sem2_accommodation', 0),
                    ])
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save</button>
                <a href="{{ route('fee-structures.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@include('fee-structures.partials.fee-structure-form-scripts')
@endsection
