@extends('layouts.app')
@section('title', 'Student registration')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    @unless(auth()->user()->isStudent())
        <a href="{{ route('semester-registrations.index') }}">Student registrations</a>
        <span class="mx-2">/</span>
    @endunless
    <span>Multi-step registration</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-ui-checks-grid me-2 opacity-90"></i>Student registration</h1>
    <p class="small text-muted mb-0 mt-2"><a href="{{ route('semester-registrations.index') }}">View registration records &amp; approvals</a></p>
</div>

@if(isset($inProgressList) && $inProgressList->isNotEmpty())
<div class="card card-landing mb-3 border-primary border-opacity-25">
    <div class="card-body py-3">
        <strong class="d-block mb-2"><i class="bi bi-arrow-repeat me-1"></i>Continue where you left off</strong>
        <div class="d-flex flex-wrap gap-2">
            @foreach($inProgressList as $reg)
                <a href="{{ route('registration-wizard.step', [$reg, $reg->wizard_step]) }}" class="btn btn-outline-primary btn-sm">
                    {{ $reg->semester->label ?? 'Semester' }} — step {{ $reg->wizard_step }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('registration-wizard.start') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="academic_year" class="form-label">Academic year</label>
                <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                    @foreach($academicYearOptions as $start => $label)
                        <option value="{{ $start }}" {{ (string) ($selectedAcademicYear ?? '') === (string) $start ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-person-plus me-2"></i>Who are you registering?</div>
    <div class="card-body">
        @if($semesters->isEmpty())
            <div class="alert alert-warning mb-3">
                <strong>No selectable semester for {{ $selectedAcademicYear }}/{{ $selectedAcademicYear + 1 }}.</strong>
                This screen only lists semesters that are <strong>both</strong> (1) saved for that academic year start year and (2) marked <strong>Active</strong>.
                @if($totalSameYear > 0 && $inactiveSameYear > 0)
                    You have {{ $totalSameYear }} semester row(s) for this year but {{ $inactiveSameYear }} are inactive —
                    @unless(auth()->user()->isStudent())open <a href="{{ route('semesters.index') }}">Semesters</a> and turn on @else ask staff to turn on @endunless
                    <strong>Active</strong>, or pick another year above.
                @elseif($totalSameYear === 0)
                    There are no semester rows for year <strong>{{ $selectedAcademicYear }}</strong>. Change the <strong>Academic year</strong> dropdown
                    @unless(auth()->user()->isStudent())
                        (your intake may use {{ $selectedAcademicYear - 1 }} or another start year), or add semesters under <a href="{{ route('semesters.index') }}">Semesters</a>.
                    @else
                        , or contact the office if the list should appear.
                    @endunless
                @else
                    @unless(auth()->user()->isStudent())
                        Open <a href="{{ route('semesters.index') }}">Semesters</a> and ensure at least one period is active for this year.
                    @else
                        Contact the office if semesters should be available for this year.
                    @endunless
                @endif
            </div>
        @endif
        <form action="{{ route('registration-wizard.start.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select @error('student_id') is-invalid @enderror" id="student_id" name="student_id" required>
                            <option value="">Select student</option>
                            @foreach($students as $st)
                                <option value="{{ $st->id }}" {{ old('student_id') == $st->id ? 'selected' : '' }}>{{ $st->full_name }} ({{ $st->programme->code ?? '' }})</option>
                            @endforeach
                        </select>
                        @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="semester_id" class="form-label">Semester (period)</label>
                    <select class="form-select @error('semester_id') is-invalid @enderror" id="semester_id" name="semester_id" required @if($semesters->isEmpty()) disabled @endif>
                        <option value="">Select semester</option>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ old('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                        @endforeach
                    </select>
                    @error('semester_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <button type="submit" class="btn btn-primary btn-lg" @if($semesters->isEmpty()) disabled @endif><i class="bi bi-arrow-right-circle me-1"></i> Start step-by-step registration</button>
            <a href="{{ route('semester-registrations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
