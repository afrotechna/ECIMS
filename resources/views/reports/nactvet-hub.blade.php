@extends('layouts.app')
@section('title', 'NACTVET reports')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="mx-2">/</span>
    <span>NACTVET</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-building me-2 opacity-90"></i>NACTVET reporting pack</h1>
    <p class="page-subtitle-landing mb-0">CSV exports for student register, locked results, and graduates.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing">Student register</div>
            <div class="card-body">
                <p class="small text-muted">Active students for NACTVET submission.</p>
                <a href="{{ route('reports.nactvet-export', ['academic_year' => $academicYear]) }}" class="btn btn-primary btn-sm">Download CSV</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing">Results (locked)</div>
            <div class="card-body">
                <form action="{{ route('reports.nactvet-results-export') }}" method="GET" class="row g-2">
                    <div class="col-12">
                        <select name="semester_id" class="form-select form-select-sm" required>
                            <option value="">Select semester</option>
                            @foreach(\App\Models\Semester::orderByDesc('academic_year')->orderByDesc('number')->limit(20)->get() as $s)
                            <option value="{{ $s->id }}">{{ $s->label ?? $s->periodName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">Export results CSV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing">Graduates (alumni)</div>
            <div class="card-body">
                <form action="{{ route('reports.alumni-export') }}" method="GET" class="row g-2">
                    <div class="col-12">
                        <input type="number" name="graduation_year" class="form-control form-control-sm" value="{{ now()->year }}" min="2000" max="2100">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">Export graduates CSV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
