@extends('layouts.app')
@section('title', 'Student registration — step '.$step.' of '.$total)
@php
    $isFirstSem = (int) $semester->number === 1;
    $isPaymentStep = $isPaymentStep ?? false;
    $feeStructureResolved = $feeStructureResolved ?? false;
    if ($isFirstSem) {
        $stepLabels = [1 => 'Student details', 2 => 'Guardian / parent', 3 => 'Payment', 4 => 'Requirements & property'];
    } else {
        $stepLabels = [1 => 'Payment', 2 => 'Requirements & property'];
    }
    $currentLabel = $stepLabels[$step] ?? 'Step';
    $nextLabel = $step < $total ? ($stepLabels[$step + 1] ?? 'next') : null;
@endphp
@push('styles')
<style>
    @keyframes regWizardEnter {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .reg-wizard-panel-animate {
        animation: regWizardEnter 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .reg-wizard-progress .progress-bar {
        transition: width 0.55s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .reg-wizard-track {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.25rem;
        margin-bottom: 1.25rem;
    }
    .reg-wizard-track-item {
        flex: 1;
        text-align: center;
        position: relative;
        min-width: 0;
    }
    .reg-wizard-track-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 16px;
        left: calc(50% + 18px);
        right: calc(-50% + 18px);
        height: 3px;
        background: #e2e8f0;
        z-index: 0;
        border-radius: 2px;
    }
    .reg-wizard-track-item.is-done:not(:last-child)::after {
        background: linear-gradient(90deg, #22c55e, #16a34a);
    }
    .reg-wizard-dot {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        margin: 0 auto 0.35rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        position: relative;
        z-index: 1;
        border: 3px solid #e2e8f0;
        background: #fff;
        color: #94a3b8;
        transition: border-color 0.3s ease, background 0.3s ease, color 0.3s ease, transform 0.25s ease;
    }
    .reg-wizard-track-item.is-done .reg-wizard-dot {
        border-color: #22c55e;
        background: #22c55e;
        color: #fff;
    }
    .reg-wizard-track-item.is-active .reg-wizard-dot {
        border-color: #2563eb;
        background: #2563eb;
        color: #fff;
        transform: scale(1.06);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
    }
    .reg-wizard-track-item.is-upcoming .reg-wizard-dot {
        border-color: #cbd5e1;
        background: #f8fafc;
    }
    .reg-wizard-track-item.is-locked .reg-wizard-dot {
        opacity: 0.45;
    }
    .reg-wizard-track-label {
        font-size: 0.68rem;
        line-height: 1.25;
        color: #64748b;
        padding: 0 2px;
    }
    .reg-wizard-track-item.is-active .reg-wizard-track-label {
        color: #1e40af;
        font-weight: 600;
    }
    @media (max-width: 767px) {
        .reg-wizard-track-label { font-size: 0.62rem; }
        .reg-wizard-dot { width: 28px; height: 28px; font-size: 0.7rem; }
    }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    @unless(auth()->user()->isStudent())
        <a href="{{ route('semester-registrations.index') }}">Student registrations</a>
    @else
        <a href="{{ route('my.registrations') }}">My registrations</a>
    @endunless
    <span class="mx-2">/</span>
    <span>Step {{ $step }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-ui-checks-grid me-2 opacity-90"></i>{{ $currentLabel }}</h1>
    <p class="page-subtitle-landing mb-0">{{ $semester->label }} · {{ $student->reg_no }} — {{ $student->full_name }}</p>
</div>

<div class="card card-landing mb-3 border-0 shadow-sm">
    <div class="card-body py-3">
        <div class="reg-wizard-track" role="navigation" aria-label="Registration steps">
            @for($i = 1; $i <= $total; $i++)
                @php
                    $isActive = $i === $step;
                    $isDone = $i < $step;
                    $isUnlocked = $i <= $wizardStep;
                    $isLocked = ! $isUnlocked;
                    $stateClass = $isActive ? 'is-active' : ($isDone ? 'is-done' : ($isLocked ? 'is-locked is-upcoming' : 'is-upcoming'));
                @endphp
                <div class="reg-wizard-track-item {{ $stateClass }}">
                    <div class="reg-wizard-dot" aria-current="{{ $isActive ? 'step' : 'false' }}">
                        @if($isDone)
                            <i class="bi bi-check-lg"></i>
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <div class="reg-wizard-track-label">{{ $stepLabels[$i] ?? 'Step '.$i }}</div>
                </div>
            @endfor
        </div>
        <div class="reg-wizard-progress mb-0">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Step {{ $step }} of {{ $total }}</span>
                <span>{{ $percent }}%</span>
            </div>
            <div class="progress" style="height:10px;" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-primary" style="width: {{ $percent }}%"></div>
            </div>
        </div>
    </div>
</div>

<div class="card card-landing reg-wizard-panel-animate">
    <div class="card-header-landing d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-file-earmark-text me-2"></i>{{ $currentLabel }}</span>
        @if($step > 1)
            <a href="{{ route('registration-wizard.step', [$semester_registration, $step - 1]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Previous step
            </a>
        @endif
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('registration-wizard.step.save', [$semester_registration, $step]) }}" id="regWizardForm">
            @csrf

            @if($isFirstSem && $step === 1)
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $student->first_name) }}" required autocomplete="given-name">
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle name</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $student->middle_name) }}" autocomplete="additional-name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $student->last_name) }}" required autocomplete="family-name">
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">—</option>
                            <option value="M" {{ old('gender', $student->gender) === 'M' ? 'selected' : '' }}>M</option>
                            <option value="F" {{ old('gender', $student->gender) === 'F' ? 'selected' : '' }}>F</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}" autocomplete="email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone) }}" autocomplete="tel">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NTA level</label>
                        <select name="nta_level" class="form-select">
                            <option value="">—</option>
                            @foreach(\App\Models\Student::NTA_LEVELS as $val => $lbl)
                                <option value="{{ $val }}" {{ (string) old('nta_level', $student->nta_level) === (string) $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Class / stream</label>
                        <input type="text" name="class_group" class="form-control" value="{{ old('class_group', $student->class_group) }}" placeholder="e.g. Group A">
                    </div>
                </div>
            @endif

            @if($isFirstSem && $step === 2)
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Guardian / parent name</label>
                        <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $student->guardian_name) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Guardian phone</label>
                        <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $student->guardian_phone) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="guardian_relationship" class="form-control" value="{{ old('guardian_relationship', $student->guardian_relationship) }}" placeholder="e.g. Father">
                    </div>
                </div>
            @endif

            @if($isPaymentStep)
                @include('registration-wizard.partials.payment-step', [
                    'feeSlotsByKey' => $feeSlotsByKey ?? [],
                    'paymentAcademicYear' => $paymentAcademicYear ?? (int) $semester->academic_year,
                    'feeStructureResolved' => $feeStructureResolved,
                    'paymentMethods' => $paymentMethods ?? \App\Models\Payment::methods(),
                    'student' => $student,
                    'paymentTuitionDefault' => $isFirstSem ? 'new_student' : 'continue',
                ])
            @endif

            @if(($isFirstSem && $step === 4) || (!$isFirstSem && $step === 2))
                @php
                    $supplySlot = $student->physicalSuppliesForSemester((int) $semester->academic_year, (int) $semester->number);
                    $glovesSel = old('supply_gloves_submitted', $supplySlot !== null && array_key_exists('gloves', $supplySlot) ? ($supplySlot['gloves'] ? '1' : '0') : '');
                    $reamSel = old('supply_ream_submitted', $supplySlot !== null && array_key_exists('ream', $supplySlot) ? ($supplySlot['ream'] ? '1' : '0') : '');
                @endphp
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-light border py-2 small mb-0">
                            <strong>Supplies for {{ $semester->label }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Clinical gloves brought / submitted</label>
                        <select name="supply_gloves_submitted" class="form-select @error('supply_gloves_submitted') is-invalid @enderror" required>
                            <option value="" disabled {{ $glovesSel === '' ? 'selected' : '' }}>— Select —</option>
                            <option value="1" {{ $glovesSel === '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ $glovesSel === '0' ? 'selected' : '' }}>No</option>
                        </select>
                        @error('supply_gloves_submitted')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">One ream of A4 paper brought / submitted</label>
                        <select name="supply_ream_submitted" class="form-select @error('supply_ream_submitted') is-invalid @enderror" required>
                            <option value="" disabled {{ $reamSel === '' ? 'selected' : '' }}>— Select —</option>
                            <option value="1" {{ $reamSel === '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ $reamSel === '0' ? 'selected' : '' }}>No</option>
                        </select>
                        @error('supply_ream_submitted')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Joining instructions (non-academic) submitted</label>
                        <select name="joining_instructions_submitted" class="form-select">
                            <option value="">—</option>
                            <option value="Yes" {{ old('joining_instructions_submitted', $student->joining_instructions_submitted) === 'Yes' ? 'selected' : '' }}>Yes</option>
                            <option value="No" {{ old('joining_instructions_submitted', $student->joining_instructions_submitted) === 'No' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    @include('students.partials.certificates-submitted-fields', ['student' => $student])
                    <div class="col-md-4">
                        <label class="form-label">Class property received</label>
                        <select name="class_property_received" class="form-select">
                            <option value="">—</option>
                            <option value="Yes" {{ old('class_property_received', $student->class_property_received) === 'Yes' ? 'selected' : '' }}>Yes</option>
                            <option value="No" {{ old('class_property_received', $student->class_property_received) === 'No' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Chair number</label>
                        <input type="text" name="chair_number" class="form-control" value="{{ old('chair_number', $student->chair_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Table number</label>
                        <input type="text" name="table_number" class="form-control" value="{{ old('table_number', $student->table_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reporting status</label>
                        <select name="reporting_status" class="form-select">
                            <option value="">—</option>
                            @foreach(\App\Models\Student::REPORTING_STATUSES as $k => $lbl)
                                <option value="{{ $k }}" {{ old('reporting_status', $student->reporting_status) === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reporting date</label>
                        <input type="date" name="reporting_date" class="form-control" value="{{ old('reporting_date', $student->reporting_date?->format('Y-m-d')) }}">
                    </div>
                </div>
            @endif

            <hr class="my-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if($step < $total)
                    <button type="submit" class="btn btn-primary" id="regWizardContinueBtn" @if($isPaymentStep && !$feeStructureResolved) disabled @endif>
                        <i class="bi bi-check2 me-1"></i> Save &amp; continue
                        @if($nextLabel)<span class="d-none d-md-inline opacity-75"> — {{ $nextLabel }}</span>@endif
                        <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                @else
                    <button type="submit" class="btn btn-success btn-lg px-4" @if($isPaymentStep && !$feeStructureResolved) disabled @endif>
                        <i class="bi bi-send-check me-1"></i> Submit registration
                    </button>
                @endif
                @if(auth()->user()->isStudent())
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Exit</a>
                @else
                    <a href="{{ route('semester-registrations.index') }}" class="btn btn-outline-secondary">Exit to list</a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
