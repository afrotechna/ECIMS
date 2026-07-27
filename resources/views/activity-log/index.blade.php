@extends('layouts.app')
@section('title', 'Activity Log')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Activity Log</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-journal-text me-2 opacity-90"></i>Activity Log</h1>
        <p class="page-subtitle-landing mb-0">Audit trail of system actions.</p>
    </div>
    @if(!empty($hasActivityLog))
    <a href="{{ route('activity-log.export', request()->query()) }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-download me-1"></i>Export CSV</a>
    @endif
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($actions ?? [] as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($users ?? [] as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name ?? $u->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Entries</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                    <tr>
                        <td>{{ $e->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $e->user->email ?? '—' }}</td>
                        <td><code class="small">{{ $e->action }}</code></td>
                        <td>{{ $e->subject_type ? class_basename($e->subject_type) . ' #' . $e->subject_id : '—' }}</td>
                        <td class="small text-muted">{{ Str::limit($e->description, 60) }}</td>
                        <td class="small">{{ $e->ip ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No activity log entries. Ensure the <code>activity_log</code> table exists and migrations have been run.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($entries->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
