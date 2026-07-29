@extends('layouts.app')
@section('title', 'Staff Leave Applications')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Staff Leave Applications</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-calendar-x me-2 opacity-90"></i>Staff Leave Applications</h1>
        <p class="page-subtitle-landing mb-0">View and approve staff leave requests (14 to 28 days).</p>
    </div>
    <a href="{{ route('leave-applications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New application</a>
</div>
<div class="card card-landing mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(\App\Models\LeaveApplication::STATUSES as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
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
                    <tr><th>Staff</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th><th>Decision by</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                    <tr>
                        <td>{{ $app->staffUser?->staffDisplayName() ?? '—' }}</td>
                        <td>{{ $app->from_date->format('d/m/Y') }}</td>
                        <td>{{ $app->to_date->format('d/m/Y') }}</td>
                        <td>{{ $app->from_date->diffInDays($app->to_date) + 1 }}</td>
                        <td>{{ Str::limit($app->reason, 40) }}</td>
                        <td><span class="badge bg-{{ $app->status === 'approved' ? 'success' : ($app->status === 'rejected' ? 'danger' : 'warning') }}">{{ \App\Models\LeaveApplication::STATUSES[$app->status] ?? $app->status }}</span></td>
                        <td class="small">
                            @if($app->approvedBy)
                                {{ $app->approvedBy->staffDisplayName() }}
                                @if(\App\Models\User::normalizeRoleSlug((string) $app->approvedBy->role) !== 'principal')
                                    <br><span class="text-muted">(on behalf of Principal)</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if(($canApproveLeave ?? false) && $app->status === 'pending')
                                <form action="{{ route('leave-applications.approve', $app) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-success">Approve</button></form>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $app->id }}">Reject</button>
                                <div class="modal fade" id="rejectModal{{ $app->id }}" tabindex="-1">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <form action="{{ route('leave-applications.reject', $app) }}" method="POST">
                                            @csrf
                                            <div class="modal-header"><h5 class="modal-title">Reject leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body"><label class="form-label">Notes (optional)</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Reject</button></div>
                                        </form>
                                    </div></div>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No staff leave applications.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if($applications->hasPages())
<div class="mt-3">{{ $applications->links() }}</div>
@endif
@endsection
