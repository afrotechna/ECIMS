@extends('layouts.app')
@section('title', 'Student attendance')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Student attendance</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-fingerprint me-2 opacity-90"></i>Student attendance</h1>
        <p class="page-subtitle-landing mb-0">From ZKTeco biometric terminal punches. Status is computed from imported logs — nothing is marked manually.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('student-attendance.import') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-upload me-1"></i>Import punches</a>
        <a href="{{ route('student-attendance.mapping') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-link-45deg me-1"></i>Biometric ID mapping</a>
    </div>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr><th>Reg No</th><th>Student</th><th>Status</th><th>First punch</th><th>Last punch</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    @php $summary = $summaries->get($s->id); @endphp
                    <tr>
                        <td><code>{{ $s->reg_no }}</code></td>
                        <td>{{ $s->full_name }}</td>
                        <td>
                            @if($summary)
                                <span class="badge bg-success">Present</span>
                            @else
                                <span class="badge bg-secondary">No punch</span>
                            @endif
                        </td>
                        <td class="small">{{ $summary ? $summary['first']->format('H:i:s') : '—' }}</td>
                        <td class="small">{{ $summary ? $summary['last']->format('H:i:s') : '—' }}</td>
                        <td class="text-end">
                            @include('partials.action-view', ['href' => route('student-attendance.show', $s), 'title' => 'Punch history'])
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No active students.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
