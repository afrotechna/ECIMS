@extends('layouts.app')

@section('title', 'Payment details')

@push('styles')
@include('reports.partials.finance-report-styles')
<style>
    .pay-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.1rem 1.5rem;
        margin-bottom: 1.5rem;
    }
    .pay-detail-item .label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .2rem;
    }
    .pay-detail-item .value { font-size: .9375rem; font-weight: 600; color: #0f172a; }
    .pay-breakdown {
        border: 1px solid #e2e8f0;
        border-radius: .625rem;
        overflow: hidden;
    }
    .pay-breakdown-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .7rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: .875rem;
    }
    .pay-breakdown-row:last-child { border-bottom: none; }
    .pay-breakdown-row .fee-label { display: flex; align-items: center; gap: .5rem; font-weight: 600; color: #334155; }
    .pay-breakdown-row .fee-label i { color: #94a3b8; }
    .pay-breakdown-row .fee-amount { font-weight: 700; font-variant-numeric: tabular-nums; color: #0f172a; }
    .pay-breakdown-row.total { background: #f8fafc; }
    .pay-breakdown-row.total .fee-label { color: #0d3651; }
    .pay-breakdown-row.total .fee-amount { color: #0d3651; font-size: 1rem; }
</style>
@endpush

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('payments.index') }}">Finance · Payment history</a>
    <span class="mx-2">/</span>
    <span>Payment #{{ $payment->id }}</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-receipt me-2 opacity-90"></i>Payment #{{ $payment->id }}</h1>
        <p class="page-subtitle-landing mb-0">{{ $payment->student->full_name ?? '—' }} &middot; {{ $payment->student->reg_no ?? '—' }}</p>
    </div>
    <div class="fin-kpi" style="margin:0;">
        <div class="fin-kpi-icon fin-kpi-icon--green"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="fin-kpi-label">Amount paid</div>
            <div class="fin-kpi-value">{{ number_format($payment->amount) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-info-circle me-2"></i>Details</div>
    <div class="card-body">
        <div class="pay-detail-grid">
            <div class="pay-detail-item">
                <div class="label">Student</div>
                <div class="value">{{ $payment->student->full_name ?? '—' }}</div>
            </div>
            @if($payment->academic_year)
            <div class="pay-detail-item">
                <div class="label">Academic session</div>
                <div class="value">{{ \App\Support\AcademicSession::label((int) $payment->academic_year) }}</div>
            </div>
            @endif
            @if($payment->semesterLabel())
            <div class="pay-detail-item">
                <div class="label">Semester</div>
                <div class="value">{{ $payment->semesterLabel() }}</div>
            </div>
            @endif
            <div class="pay-detail-item">
                <div class="label">Method</div>
                <div class="value">{{ \App\Models\Payment::methods()[$payment->payment_method] ?? $payment->payment_method }}</div>
            </div>
            <div class="pay-detail-item">
                <div class="label">Date</div>
                <div class="value">{{ $payment->paid_at->format('d M Y, H:i') }}</div>
            </div>
            <div class="pay-detail-item">
                <div class="label">Control / reference no.</div>
                <div class="value">{{ $payment->reference ?: '—' }}</div>
            </div>
            <div class="pay-detail-item">
                <div class="label">Received by</div>
                <div class="value">{{ $payment->receiver->name ?? '—' }}</div>
            </div>
        </div>

        @php
            $feeLabels = ['tuition' => 'Tuition', 'nhif' => 'NHIF', 'nactvet_qa' => 'NACTVET QA'];
            $feeIcons = ['tuition' => 'bi-mortarboard', 'nhif' => 'bi-heart-pulse', 'nactvet_qa' => 'bi-patch-check'];
            $breakdown = collect($payment->allocation ?? [])
                ->filter(fn ($v, $k) => $k !== 'refs' && is_numeric($v) && (float) $v > 0);
        @endphp
        @if($breakdown->isNotEmpty())
        <div class="label mb-2">Fee breakdown</div>
        <div class="pay-breakdown mb-2">
            @foreach($breakdown as $key => $value)
            <div class="pay-breakdown-row">
                <span class="fee-label"><i class="bi {{ $feeIcons[$key] ?? 'bi-cash-coin' }}"></i>{{ $feeLabels[$key] ?? \Illuminate\Support\Str::headline($key) }}</span>
                <span class="fee-amount">{{ number_format((float) $value) }} TZS</span>
            </div>
            @endforeach
            <div class="pay-breakdown-row total">
                <span class="fee-label">Total</span>
                <span class="fee-amount">{{ number_format($breakdown->sum()) }} TZS</span>
            </div>
        </div>
        @endif
        <hr class="my-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-receipt me-1"></i>Print receipt</a>
            <a href="{{ route('payments.reverse.form', $payment) }}" class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reverse payment</a>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Back to list</a>
        </div>
    </div>
</div>
@endsection
