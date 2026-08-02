@extends('layouts.app')
@section('title', 'Attendance — '.$student->full_name)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('student-attendance.index') }}">Student attendance</a>
    <span class="mx-2">/</span>
    <span>{{ $student->full_name }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-fingerprint me-2 opacity-90"></i>{{ $student->full_name }}</h1>
    <p class="page-subtitle-landing mb-0"><code>{{ $student->nactvet_reg_no ?: '—' }}</code> · Biometric ID: {{ $student->biometric_id ?? 'not mapped' }}</p>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-clock-history me-2"></i>Punch log</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr><th>Date</th><th>Time</th><th>Direction</th></tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->punched_at->format('d/m/Y') }}</td>
                        <td>{{ $log->punched_at->format('H:i:s') }}</td>
                        <td>
                            @if($log->direction === 'in')
                                <span class="badge bg-success">In</span>
                            @elseif($log->direction === 'out')
                                <span class="badge bg-secondary">Out</span>
                            @else
                                <span class="badge bg-light text-dark">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted py-5">No punches in this date range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
