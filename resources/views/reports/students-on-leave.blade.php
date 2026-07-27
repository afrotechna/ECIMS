@extends('layouts.app')
@section('title', 'Staff on Leave')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="mx-2">/</span>
    <span>Staff on leave</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-calendar-x me-2 opacity-90"></i>Staff on leave</h1>
    <p class="page-subtitle-landing mb-0">Approved staff leave with return date on or after today.</p>
</div>
<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Staff ID</th><th>Name</th><th>Role</th><th>From</th><th>Return</th><th>Days</th><th>Reason</th></tr></thead>
                <tbody>
                    @forelse($applications as $app)
                    <tr>
                        <td>{{ $app->staffUser->staff_id ?? '-' }}</td>
                        <td>{{ $app->staffUser?->staffDisplayName() ?? '' }}</td>
                        <td>{{ \App\Models\User::roleLabel($app->staffUser->role ?? null) }}</td>
                        <td>{{ $app->from_date->format('d/m/Y') }}</td>
                        <td>{{ $app->to_date->format('d/m/Y') }}</td>
                        <td>{{ $app->from_date->diffInDays($app->to_date) + 1 }}</td>
                        <td>{{ Str::limit($app->reason, 50) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No staff currently on approved leave.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
