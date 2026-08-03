@extends('layouts.app')
@section('title', 'Fee Collection Summary')
@push('styles')
@include('reports.partials.finance-report-styles')
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Finance · Reports</a>
    <span class="mx-2">/</span>
    <span>Fee collection</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pie-chart me-2 opacity-90"></i>Fee collection summary</h1>
    <p class="page-subtitle-landing mb-0">Expected fees vs cash collected by programme for {{ \App\Support\AcademicSession::label((int) $academicYear) }}.</p>
</div>

<div class="card card-landing mb-3 fin-filter-card">
    <div class="card-header-landing py-2"><i class="bi bi-funnel me-2"></i>Academic year</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">Session</label>
                <select name="academic_year" class="form-select form-select-sm" data-no-search>
                    @foreach($yearOptions as $y => $label)
                    <option value="{{ $y }}" {{ (int) $academicYear === (int) $y ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="fin-kpi-row">
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--navy"><i class="bi bi-bullseye"></i></div>
        <div>
            <div class="fin-kpi-label">Expected</div>
            <div class="fin-kpi-value">{{ number_format($summaryTotals['expected']) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--green"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="fin-kpi-label">Collected</div>
            <div class="fin-kpi-value">{{ number_format($summaryTotals['collected']) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--amber"><i class="bi bi-percent"></i></div>
        <div>
            <div class="fin-kpi-label">Collection rate</div>
            <div class="fin-kpi-value">{{ $summaryTotals['expected'] > 0 ? $summaryTotals['rate'].'%' : '—' }}</div>
            <div class="fin-kpi-sub">{{ number_format($summaryTotals['students']) }} active students</div>
        </div>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing py-2"><i class="bi bi-list-ul me-2"></i>By programme</div>
    <div class="card-body p-0">
        @forelse($data as $row)
        @php
            $rate = $row['expected'] > 0 ? min(100, round(100 * $row['collected'] / $row['expected'])) : 0;
            $barClass = $rate >= 75 ? 'fin-progress-bar--ok' : ($rate >= 40 ? 'fin-progress-bar' : 'fin-progress-bar--warn');
        @endphp
        <div class="fin-prog-row">
            <div class="flex-grow-1 pe-md-3">
                <div class="fin-prog-name">{{ $row['programme'] }}</div>
                <div class="fin-prog-meta">
                    {{ number_format($row['students']) }} students ·
                    Expected {{ number_format($row['expected']) }} ·
                    Collected {{ number_format($row['collected']) }}
                </div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <div class="fin-progress-wrap flex-grow-1">
                        <div class="fin-progress"><div class="{{ $barClass }}" style="width: {{ $rate }}%"></div></div>
                    </div>
                    <span class="small fw-bold text-nowrap">{{ $row['expected'] > 0 ? number_format($rate, 1).'%' : '—' }}</span>
                </div>
            </div>
        </div>
        @empty
        <p class="text-center text-muted py-5 mb-0">No active fee schedules for {{ \App\Support\AcademicSession::label((int) $academicYear) }}. Add schedules under <a href="{{ route('fee-structures.index') }}">Fees</a>.</p>
        @endforelse
    </div>
    @if($data->isNotEmpty())
    <div class="card-footer bg-light border-0 py-2 small text-muted">
        Expected = schedule total × active students per programme. Collected = payments recorded in calendar year {{ $academicYear }}.
    </div>
    @endif
</div>
@endsection
