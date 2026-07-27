@extends('layouts.app')
@section('title', 'Income Report')
@push('styles')
@include('reports.partials.finance-report-styles')
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Finance · Reports</a>
    <span class="mx-2">/</span>
    <span>Income</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3 mb-1">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-cash-stack me-2 opacity-90"></i>Income report</h1>
        <p class="page-subtitle-landing mb-0">Cash received from {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}.</p>
    </div>
    <a href="{{ route('reports.income.export', ['from' => $from, 'to' => $to]) }}" class="btn btn-light btn-sm text-dark">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
</div>

@include('reports.partials.finance-filter-dates', ['from' => $from, 'to' => $to])

<div class="fin-kpi-row">
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--green"><i class="bi bi-wallet2"></i></div>
        <div>
            <div class="fin-kpi-label">Total collected</div>
            <div class="fin-kpi-value">{{ number_format($total) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--navy"><i class="bi bi-receipt"></i></div>
        <div>
            <div class="fin-kpi-label">Transactions</div>
            <div class="fin-kpi-value">{{ number_format($paymentCount) }}</div>
            <div class="fin-kpi-sub">Receipts in range</div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--amber"><i class="bi bi-calculator"></i></div>
        <div>
            <div class="fin-kpi-label">Average receipt</div>
            <div class="fin-kpi-value">{{ $paymentCount > 0 ? number_format($total / $paymentCount) : '0' }}</div>
            <div class="fin-kpi-sub">TZS per payment</div>
        </div>
    </div>
</div>

@if($byMethod->isNotEmpty())
<div class="card card-landing mb-3">
    <div class="card-header-landing py-2"><i class="bi bi-pie-chart me-2"></i>By payment method</div>
    <div class="card-body">
        <div class="fin-method-pills">
            @foreach($byMethod as $row)
            <span class="fin-method-pill">
                {{ \App\Models\Payment::methods()[$row->payment_method] ?? ucfirst($row->payment_method) }}:
                <strong>{{ number_format($row->total) }} TZS</strong>
                <span class="text-muted">({{ $row->cnt }})</span>
            </span>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
        <span><i class="bi bi-list-ul me-2"></i>Payment lines</span>
        <span class="badge bg-light text-dark">{{ $payments->total() }} total</span>
    </div>
    <div class="card-body p-0 fin-pay-table-only">
        <table class="table fin-report-table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Reference</th>
                    <th>Method</th>
                    <th class="fin-money">Amount (TZS)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td>
                        <span class="d-block fw-semibold">{{ $p->paid_at->format('d/m/Y') }}</span>
                        <span class="small text-muted">{{ $p->paid_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <span class="d-block fw-semibold">{{ $p->student->full_name ?? '—' }}</span>
                        <span class="small text-muted">{{ $p->student->reg_no ?? '—' }}</span>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $p->student->programme->code ?? '—' }}</span></td>
                    <td><code class="small">{{ $p->reference ?? $p->id }}</code></td>
                    <td><span class="badge bg-secondary">{{ \App\Models\Payment::methods()[$p->payment_method] ?? $p->payment_method }}</span></td>
                    <td class="fin-money fin-money--lg">{{ number_format($p->amount) }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('payments.show', $p) }}" class="btn btn-sm btn-outline-primary">View</a>
                        <a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Receipt</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No payments in this date range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body fin-pay-cards-only">
        @forelse($payments as $p)
        <article class="fin-pay-card">
            <div class="fin-pay-card-head">
                <div>
                    <div class="fw-semibold">{{ $p->student->full_name ?? '—' }}</div>
                    <div class="small text-muted">{{ $p->paid_at->format('d/m/Y H:i') }} · {{ $p->student->reg_no ?? '—' }}</div>
                </div>
                <div class="fin-pay-card-amount">{{ number_format($p->amount) }} TZS</div>
            </div>
            <dl class="fin-pay-card-dl">
                <dt>Programme</dt><dd>{{ $p->student->programme->code ?? '—' }}</dd>
                <dt>Method</dt><dd>{{ \App\Models\Payment::methods()[$p->payment_method] ?? $p->payment_method }}</dd>
                <dt>Reference</dt><dd><code class="small">{{ $p->reference ?? $p->id }}</code></dd>
            </dl>
            <div class="d-flex gap-2 mt-2">
                <a href="{{ route('payments.show', $p) }}" class="btn btn-sm btn-outline-primary">View</a>
                <a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Receipt</a>
            </div>
        </article>
        @empty
        <p class="text-center text-muted py-4 mb-0">No payments in this date range.</p>
        @endforelse
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $payments->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
