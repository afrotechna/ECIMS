@extends('layouts.app')
@section('title', 'Quick student registration')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('semester-registrations.index') }}">Student registrations</a>
    <span class="mx-2">/</span>
    <span>New Registration</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-calendar-plus me-2 opacity-90"></i>Quick registration submit</h1>
    <p class="page-subtitle-landing mb-0">Creates a pending registration without the guided steps. Prefer <a href="{{ route('registration-wizard.start') }}">Student registration (steps)</a> for the full flow.</p>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('semester-registrations.create') }}" class="row g-3 align-items-end">
            @if($preselectedStudent)
                <input type="hidden" name="student_id" value="{{ $preselectedStudent }}">
            @endif
            @if($preselectedSemester)
                <input type="hidden" name="semester_id" value="{{ $preselectedSemester }}">
            @endif
            <div class="col-md-4">
                <label for="academic_year" class="form-label">Academic year</label>
                <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                    <option value="">All active semesters</option>
                    @foreach($academicYearOptions as $start => $label)
                        <option value="{{ $start }}" {{ (string) ($selectedAcademicYear ?? '') === (string) $start ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Registration details</div>
    <div class="card-body">
        <form action="{{ route('semester-registrations.store') }}" method="POST">
            @csrf
            @if($semesters->isEmpty())
            <div class="alert alert-warning mb-3">No active semester for this filter. <a href="{{ route('semesters.index') }}">Semesters</a></div>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student</label>
                    <select class="form-select @error('student_id') is-invalid @enderror" id="student_id" name="student_id" required>
                        <option value="">Select student</option>
                        @foreach($students as $st)
                        <option value="{{ $st->id }}" {{ old('student_id', $preselectedStudent ?? '') == $st->id ? 'selected' : '' }}>{{ $st->reg_no }} - {{ $st->full_name }}</option>
                        @endforeach
                    </select>
                    @error('student_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="semester_id" class="form-label">Semester</label>
                    <select class="form-select @error('semester_id') is-invalid @enderror" id="semester_id" name="semester_id" required @if($semesters->isEmpty()) disabled @endif>
                        <option value="">Select semester</option>
                        @foreach($semesters as $s)
                        <option value="{{ $s->id }}" {{ old('semester_id', $preselectedSemester ?? '') == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                        @endforeach
                    </select>
                    @error('semester_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" @if($semesters->isEmpty()) disabled @endif><i class="bi bi-check-lg me-1"></i> Submit Registration</button>
                <a href="{{ route('semester-registrations.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
