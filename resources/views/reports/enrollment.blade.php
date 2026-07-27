@extends('layouts.app')
@section('title', 'Enrollment Report')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Finance · Reports</a>
    <span class="mx-2">/</span>
    <span>Enrollment</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-people me-2 opacity-90"></i>Enrollment Report</h1>
        <p class="page-subtitle-landing mb-0">By programme or intake year.</p>
    </div>
    <a href="{{ route('reports.enrollment.export', request()->query()) }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-download me-1"></i> Export CSV</a>
</div>

<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-funnel me-2"></i>Filters</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small">Group by</label><select name="by" class="form-select form-select-sm"><option value="programme" {{ request('by') === 'programme' ? 'selected' : '' }}>Programme</option><option value="intake" {{ request('by') === 'intake' ? 'selected' : '' }}>Intake year</option></select></div>
    <div class="col-md-3"><label class="form-label small">Intake year</label><select name="intake_year" class="form-select form-select-sm"><option value="">All</option>@foreach($intakeYears as $y)<option value="{{ $y }}" {{ request('intake_year') == $y ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Apply</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Results</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th class="text-end">Count</th></tr></thead>
                <tbody>@forelse($data as $row)<tr><td>{{ $row['name'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@empty<tr><td colspan="2" class="text-center text-muted py-5">No data</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>
</div>
@endsection
