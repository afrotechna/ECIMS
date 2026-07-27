@extends('layouts.app')
@section('title', 'Payment by Programme')
@push('styles')
@include('reports.partials.finance-report-styles')
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Finance · Reports</a>
    <span class="mx-2">/</span>
    <span>Payment by programme</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-currency-exchange me-2 opacity-90"></i>Payment by programme</h1>
    <p class="page-subtitle-landing mb-0">Collections grouped by student programme for the selected period.</p>
</div>

@include('reports.partials.finance-filter-dates', ['from' => $from, 'to' => $to])

<div class="fin-kpi-row">
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--green"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="fin-kpi-label">Grand total</div>
            <div class="fin-kpi-value">{{ number_format($grandTotal) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--navy"><i class="bi bi-receipt"></i></div>
        <div>
            <div class="fin-kpi-label">Transactions</div>
            <div class="fin-kpi-value">{{ number_format($grandCount) }}</div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--amber"><i class="bi bi-mortarboard"></i></div>
        <div>
            <div class="fin-kpi-label">Programmes</div>
            <div class="fin-kpi-value">{{ $data->count() }}</div>
        </div>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing py-2"><i class="bi bi-bar-chart me-2"></i>Summary by programme</div>
    <div class="card-body p-0">
        @php $maxTotal = max(1, (float) $data->max('total')); @endphp
        @forelse($data as $row)
        @php $pct = min(100, round(100 * $row['total'] / $maxTotal)); @endphp
        <div class="fin-prog-row">
            <div class="flex-grow-1 pe-md-3">
                <div class="fin-prog-name">{{ $row['name'] }}</div>
                <div class="fin-prog-meta">{{ number_format($row['count']) }} payment(s)</div>
                <div class="fin-progress-wrap mt-2">
                    <div class="fin-progress"><div class="fin-progress-bar" style="width: {{ $pct }}%"></div></div>
                </div>
            </div>
            <div class="fin-money fin-money--lg text-md-end mt-2 mt-md-0">{{ number_format($row['total']) }} TZS</div>
        </div>
        @empty
        <p class="text-center text-muted py-5 mb-0">No payments recorded for this date range.</p>
        @endforelse
    </div>
    @if($data->isNotEmpty())
    <div class="card-footer bg-light border-0 py-2 d-flex justify-content-between small fw-semibold">
        <span>Total</span>
        <span>{{ number_format($grandTotal) }} TZS · {{ number_format($grandCount) }} payments</span>
    </div>
    @endif
</div>
@endsection
