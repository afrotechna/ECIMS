@extends('layouts.app')
@section('title', 'Graduation Clearance')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="mx-2">/</span>
    <span>Graduation clearance</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-clipboard-check me-2 opacity-90"></i>Graduation clearance</h1>
        <p class="page-subtitle-landing mb-0">Library, finance, accommodation, academic checklist per student.</p>
    </div>
    <a href="{{ route('graduation-clearances.index') }}" class="btn btn-primary btn-sm">Manage clearances</a>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-0">Show</label>
                <select name="cleared" class="form-select form-select-sm">
                    <option value="all" {{ request('cleared', 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="yes" {{ request('cleared') === 'yes' ? 'selected' : '' }}>Fully cleared only</option>
                    <option value="no" {{ request('cleared') === 'no' ? 'selected' : '' }}>Not fully cleared</option>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Library</th>
                        <th>Finance</th>
                        <th>Accommodation</th>
                        <th>Academic</th>
                        <th>Cleared</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clearances as $c)
                    <tr>
                        <td>{{ $c->student->reg_no ?? '' }} — {{ $c->student->full_name ?? '' }}</td>
                        <td>{{ $c->library_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                        <td>{{ $c->finance_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                        <td>{{ $c->accommodation_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                        <td>{{ $c->academic_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                        <td>@if($c->isFullyCleared())<span class="badge bg-success">Yes</span>@else<span class="badge bg-secondary">No</span>@endif</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No graduation clearances recorded. Add from student profile or Manage clearances.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
