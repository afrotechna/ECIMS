@extends('layouts.app')
@section('title', 'Arrears')
@push('styles')
@include('reports.partials.finance-report-styles')
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Finance · Reports</a>
    <span class="mx-2">/</span>
    <span>Arrears</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-exclamation-triangle me-2 opacity-90"></i>Arrears report</h1>
        <p class="page-subtitle-landing mb-0">Students with an outstanding fee balance on the ledger.</p>
    </div>
    <a href="{{ route('reports.arrears.export') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-download me-1"></i> Export CSV</a>
</div>

<div class="fin-kpi-row">
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--rose"><i class="bi bi-exclamation-circle"></i></div>
        <div>
            <div class="fin-kpi-label">Total arrears</div>
            <div class="fin-kpi-value">{{ number_format($totalArrears) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--navy"><i class="bi bi-people"></i></div>
        <div>
            <div class="fin-kpi-label">Students owing</div>
            <div class="fin-kpi-value">{{ number_format($students->count()) }}</div>
            <div class="fin-kpi-sub">Active accounts with balance &gt; 0</div>
        </div>
    </div>
    @if($students->count() > 0)
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--amber"><i class="bi bi-graph-down"></i></div>
        <div>
            <div class="fin-kpi-label">Average balance</div>
            <div class="fin-kpi-value">{{ number_format($totalArrears / $students->count()) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    @endif
</div>

<div class="card card-landing">
    <div class="card-header-landing py-2"><i class="bi bi-list-ul me-2"></i>Students with balance due</div>
    <div class="card-body p-0">
        @forelse($students as $s)
        <div class="fin-prog-row">
            <div>
                <div class="fin-prog-name">{{ $s->full_name }}</div>
                <div class="fin-prog-meta">
                    <span class="me-2"><strong>{{ $s->reg_no }}</strong></span>
                    {{ $s->programme->code ?? '—' }}
                    @if($s->programme?->name)<span class="text-muted"> · {{ $s->programme->name }}</span>@endif
                </div>
            </div>
            <div class="text-md-end">
                <div class="fin-money fin-money--lg text-danger mb-1">{{ number_format($s->balance) }} TZS</div>
                <a href="{{ route('students.ledger', $s) }}" class="btn btn-sm btn-outline-primary">Open ledger</a>
            </div>
        </div>
        @empty
        <p class="text-center text-muted py-5 mb-0"><i class="bi bi-check-circle text-success me-1"></i> No students with arrears.</p>
        @endforelse
    </div>
</div>
@endsection
