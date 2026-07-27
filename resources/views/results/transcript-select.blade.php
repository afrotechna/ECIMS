@extends('layouts.app')
@section('title', 'Transcript')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('courses.index') }}">Modules</a>
    <span class="mx-2">/</span>
    <a href="{{ route('results.index') }}">Results</a>
    <span class="mx-2">/</span>
    <span>Transcript</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-file-text me-2 opacity-90"></i>View Transcript</h1>
        <p class="page-subtitle-landing mb-0">Select a student to view their academic transcript.</p>
    </div>
    <a href="{{ route('results.index') }}" class="btn btn-outline-light btn-sm">Back to Results</a>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-person me-2"></i>Select student</div>
    <div class="card-body">
        <form method="GET" action="{{ route('results.transcript') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Student</label>
                <select name="student_id" class="form-select" required>
                <option value="">— Select student —</option>
                    @foreach($students as $st)
                    <option value="{{ $st->id }}">{{ $st->reg_no }} — {{ $st->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-file-text me-1"></i> View transcript</button>
            </div>
        </form>
    </div>
</div>
@endsection
