@extends('layouts.app')

@section('title', 'Payment history')

@push('styles')
@include('reports.partials.finance-report-styles')
@endpush

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Finance</span>
    <span class="mx-2">/</span>
    <span>Payment history</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-clock-history me-2 opacity-90"></i>Payment history</h1>
        <p class="page-subtitle-landing mb-0">All recorded fee payments. New payments are captured during student registration.</p>
    </div>
    <a href="{{ route('reports.income') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-graph-up me-1"></i> Income report</a>
</div>

<div class="fin-kpi-row">
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--green"><i class="bi bi-sun"></i></div>
        <div>
            <div class="fin-kpi-label">Today</div>
            <div class="fin-kpi-value">{{ number_format($todayTotal ?? 0) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--navy"><i class="bi bi-calendar-month"></i></div>
        <div>
            <div class="fin-kpi-label">This month</div>
            <div class="fin-kpi-value">{{ number_format($monthTotal ?? 0) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--amber"><i class="bi bi-database"></i></div>
        <div>
            <div class="fin-kpi-label">All receipts</div>
            <div class="fin-kpi-value">{{ number_format($allTimeCount ?? 0) }}</div>
        </div>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
        <span><i class="bi bi-list-ul me-2"></i>All payments</span>
        <span class="badge bg-light text-dark">{{ $payments->total() }} records</span>
    </div>
    <div class="card-body p-0 fin-pay-table-only">
        <table class="table fin-report-table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Session</th>
                    <th>Student</th>
                    <th>Reference</th>
                    <th class="fin-money">Amount</th>
                    <th>Method</th>
                    <th class="text-end">Actions</th>
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
                        @if($p->academic_year)
                        <span class="badge bg-light text-dark border">{{ \App\Support\AcademicSession::label((int) $p->academic_year) }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="d-block fw-semibold">{{ $p->student?->full_name ?? '—' }}</span>
                        <span class="small text-muted">{{ $p->student?->reg_no ?? '—' }}</span>
                    </td>
                    <td><code class="small">{{ $p->reference ?? $p->id }}</code></td>
                    <td class="fin-money fin-money--lg">{{ number_format($p->amount) }}</td>
                    <td><span class="badge bg-secondary">{{ \App\Models\Payment::methods()[$p->payment_method] ?? $p->payment_method }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('payments.show', $p) }}" class="btn btn-sm btn-outline-primary">View</a>
                        <a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Receipt</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">No payments recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body fin-pay-cards-only">
        @forelse($payments as $p)
        <article class="fin-pay-card">
            <div class="fin-pay-card-head">
                <div>
                    <div class="fw-semibold">{{ $p->student?->full_name ?? '—' }}</div>
                    <div class="small text-muted">{{ $p->paid_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="fin-pay-card-amount">{{ number_format($p->amount) }} TZS</div>
            </div>
            <dl class="fin-pay-card-dl">
                <dt>Reg no.</dt><dd>{{ $p->student?->reg_no ?? '—' }}</dd>
                <dt>Session</dt><dd>{{ $p->academic_year ? \App\Support\AcademicSession::label((int) $p->academic_year) : '—' }}</dd>
                <dt>Method</dt><dd>{{ \App\Models\Payment::methods()[$p->payment_method] ?? $p->payment_method }}</dd>
                <dt>Reference</dt><dd><code class="small">{{ $p->reference ?? $p->id }}</code></dd>
            </dl>
            <div class="d-flex gap-2 mt-2">
                <a href="{{ route('payments.show', $p) }}" class="btn btn-sm btn-outline-primary">View</a>
                <a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Receipt</a>
            </div>
        </article>
        @empty
        <p class="text-center text-muted py-4 mb-0">No payments recorded yet.</p>
        @endforelse
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $payments->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
