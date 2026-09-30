@extends('layouts.app')
@section('title', 'Student registration — '.$semester->label)
@php
    $isFirstSem = (int) $semester->number === 1;
    $isPaymentStep = $isPaymentStep ?? false;
    $feeStructureResolved = $feeStructureResolved ?? false;
    if ($isFirstSem) {
        $stepLabels = [1 => 'Student details', 2 => 'Guardian / parent', 3 => 'Payment', 4 => 'Requirements & property'];
    } else {
        $stepLabels = [1 => 'Payment', 2 => 'Requirements & property'];
    }
    $paymentStepNumber = $isFirstSem ? 3 : 1;
    $requirementsStepNumber = $isFirstSem ? 4 : 2;
@endphp
@push('styles')
<style>
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
    .reg-wizard-track-item a { text-decoration: none; display: block; }
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
        border-color: var(--cohas-blue-700);
        background: var(--cohas-blue-700);
        color: #fff;
        transform: scale(1.06);
        box-shadow: 0 0 0 4px var(--cohas-blue-soft);
    }
    .reg-wizard-track-item.is-locked .reg-wizard-dot {
        border-color: #cbd5e1;
        background: #f8fafc;
        opacity: 0.6;
    }
    .reg-wizard-track-label {
        font-size: 0.68rem;
        line-height: 1.25;
        color: #64748b;
        padding: 0 2px;
    }
    .reg-wizard-track-item.is-active .reg-wizard-track-label {
        color: var(--cohas-blue-900);
        font-weight: 600;
    }
    @media (max-width: 767px) {
        .reg-wizard-track-label { font-size: 0.62rem; }
        .reg-wizard-dot { width: 28px; height: 28px; font-size: 0.7rem; }
    }

    .reg-section {
        scroll-margin-top: 90px;
    }
    .reg-section-active {
        border: 1px solid var(--cohas-blue-700);
        box-shadow: 0 0 0 3px var(--cohas-blue-soft);
    }
    .reg-section-locked .card-body { position: relative; }
    .reg-section-locked fieldset { opacity: 0.55; }
    .reg-section-locked-note {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .6rem .9rem;
        border-radius: .5rem;
        background: var(--cohas-surface-muted, #f8fafc);
        color: var(--cohas-text-muted, #64748b);
        font-size: .85rem;
        margin-bottom: 1rem;
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
    <span>{{ $student->full_name }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-ui-checks-grid me-2 opacity-90"></i>Student registration</h1>
    <p class="page-subtitle-landing mb-0">{{ $semester->label }} · {{ $student->full_name }} — every section is shown below; complete them in order.</p>
</div>

<div class="card card-landing mb-3 border-0 shadow-sm">
    <div class="card-body py-3">
        <div class="reg-wizard-track" role="navigation" aria-label="Registration sections">
            @for($i = 1; $i <= $total; $i++)
                @php
                    $navIsActive = $i === $wizardStep;
                    $navIsDone = $i < $wizardStep;
                    $navIsLocked = $i > $wizardStep;
                    $navStateClass = $navIsActive ? 'is-active' : ($navIsDone ? 'is-done' : 'is-locked');
                @endphp
                <div class="reg-wizard-track-item {{ $navStateClass }}">
                    <a href="#reg-section-{{ $i }}">
                        <div class="reg-wizard-dot" aria-current="{{ $navIsActive ? 'step' : 'false' }}">
                            @if($navIsDone)
                                <i class="bi bi-check-lg"></i>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        <div class="reg-wizard-track-label">{{ $stepLabels[$i] ?? 'Section '.$i }}</div>
                    </a>
                </div>
            @endfor
        </div>
    </div>
</div>

@for($i = 1; $i <= $total; $i++)
    @php
        $isDone = $i < $wizardStep;
        $isActive = $i === $wizardStep;
        $isLocked = $i > $wizardStep;
        $sectionLabel = $stepLabels[$i] ?? 'Section '.$i;
        $isSectionPaymentStep = $i === $paymentStepNumber;
        $isSectionRequirementsStep = $i === $requirementsStepNumber;
    @endphp
    <div class="card card-landing mb-3 reg-section {{ $isActive ? 'reg-section-active' : '' }} {{ $isLocked ? 'reg-section-locked' : '' }}" id="reg-section-{{ $i }}">
        <div class="card-header-landing d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>
                @if($isDone)
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                @elseif($isLocked)
                    <i class="bi bi-lock-fill me-2 opacity-75"></i>
                @else
                    <i class="bi bi-file-earmark-text me-2"></i>
                @endif
                {{ $i }}. {{ $sectionLabel }}
            </span>
            @if($isDone)<span class="badge bg-success">Completed</span>@endif
        </div>
        <div class="card-body">
            @if($isLocked)
                <div class="reg-section-locked-note">
                    <i class="bi bi-lock"></i>
                    Complete &ldquo;{{ $stepLabels[$i - 1] ?? 'the previous section' }}&rdquo; first.
                </div>
            @endif
            <form method="POST" action="{{ route('registration-wizard.step.save', [$semester_registration, $i]) }}">
                @csrf
                <fieldset @if($isLocked) disabled @endif>

                @if($isFirstSem && $i === 1)
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

                @if($isFirstSem && $i === 2)
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Guardian / parent name</label>
                            <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $student->guardian_name ?: $student->guardianNameSuggestion()) }}">
                            @unless($student->guardian_name)
                            <div class="form-text">Suggested from the student's name &mdash; correct it if needed.</div>
                            @endunless
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Guardian phone</label>
                            <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $student->guardian_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="guardian_relationship_choice" class="form-label">Relationship</label>
                            @include('students.partials.guardian-relationship-field', ['currentValue' => old('guardian_relationship', $student->guardian_relationship)])
                        </div>
                    </div>
                @endif

                @if($isSectionPaymentStep)
                    @include('registration-wizard.partials.payment-step', [
                        'feeSlotsByKey' => $feeSlotsByKey ?? [],
                        'paymentAcademicYear' => $paymentAcademicYear ?? (int) $semester->academic_year,
                        'feeStructureResolved' => $feeStructureResolved,
                        'paymentMethods' => $paymentMethods ?? \App\Models\Payment::methods(),
                        'student' => $student,
                        'paymentTuitionDefault' => $isFirstSem ? $student->defaultTuitionCategory((int) $semester->academic_year) : 'continue',
                        'isFirstSem' => $isFirstSem,
                        'chargesNhifQa' => $chargesNhifQa ?? true,
                        'locked' => $isLocked,
                    ])
                @endif

                @if($isSectionRequirementsStep)
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
                        @if($isFirstSem)
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
                            <label class="form-label d-block">Reporting</label>
                            <div class="form-text mt-0">Marked as <strong>Reported</strong> today, {{ now()->format('d M Y') }}, once this registration completes.</div>
                        </div>
                        @endif
                    </div>
                @endif

                @unless($isLocked)
                    <hr class="my-4">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button type="submit" class="btn {{ $i === $total ? 'btn-success btn-lg px-4' : 'btn-primary' }}" @if($isSectionPaymentStep) id="regWizardContinueBtn" @endif @if($isSectionPaymentStep && !$feeStructureResolved) disabled @endif>
                            @if($i === $total)
                                <i class="bi bi-send-check me-1"></i> Submit registration
                            @elseif($isDone)
                                <i class="bi bi-check2 me-1"></i> Update
                            @else
                                <i class="bi bi-check2 me-1"></i> Save &amp; continue
                            @endif
                        </button>
                    </div>
                @endunless

                </fieldset>
            </form>
        </div>
    </div>
@endfor

<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
    @if(auth()->user()->isStudent())
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Exit</a>
    @else
        <a href="{{ route('semester-registrations.index') }}" class="btn btn-outline-secondary">Exit to list</a>
    @endif
</div>
@endsection
