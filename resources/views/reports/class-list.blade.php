@extends('layouts.app')
@section('title', 'Class List')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="mx-2">/</span>
    <span>Class List</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-people me-2 opacity-90"></i>Class List</h1>
        <p class="page-subtitle-landing mb-0">Students registered for a course in a semester.</p>
    </div>
    @if(!empty($semesterId) && !empty($courseId) && $students->isNotEmpty())
    <a href="{{ route('reports.class-list.export', ['semester_id' => $semesterId, 'course_id' => $courseId]) }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-download me-1"></i>Export CSV</a>
    @endif
</div>
<div class="card card-landing mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Semester</label>
                <select name="semester_id" class="form-select form-select-sm">
                    <option value="">Select semester</option>
                    @foreach($semesters as $s)
                    <option value="{{ $s->id }}" {{ ($semesterId ?? '') == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-0">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">Select course</option>
                    @foreach($courses as $c)
                    <option value="{{ $c->id }}" {{ ($courseId ?? '') == $c->id ? 'selected' : '' }}>{{ $c->code }} - {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Show list</button></div>
        </form>
    </div>
</div>
<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Students</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Reg No</th><th>NACTVET No</th><th>Name</th><th>Programme</th></tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    <tr>
                        <td><code>{{ $s->reg_no }}</code></td>
                        <td><code class="small">{{ $s->nactvet_reg_no }}</code></td>
                        <td>{{ $s->full_name }}</td>
                        <td>{{ $s->programme->code ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">Select a semester and course above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
