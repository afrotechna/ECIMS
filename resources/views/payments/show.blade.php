@extends('layouts.app')

@section('title', 'Payment details')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('payments.index') }}">Finance · Payment history</a>
    <span class="mx-2">/</span>
    <span>Payment #{{ $payment->id }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-receipt me-2 opacity-90"></i>Payment #{{ $payment->id }}</h1>
    <p class="page-subtitle-landing mb-0">{{ $payment->student->reg_no ?? '—' }} · {{ number_format($payment->amount) }} TZS</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-info-circle me-2"></i>Details</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="text-muted small">Student</label>
                <div class="fw-semibold">{{ $payment->student->full_name }} ({{ $payment->student->reg_no }})</div>
            </div>
            @if($payment->academic_year)
            <div class="col-md-6">
                <label class="text-muted small">Academic session</label>
                <div class="fw-semibold">{{ \App\Support\AcademicSession::label((int) $payment->academic_year) }}</div>
            </div>
            @endif
            <div class="col-md-6">
                <label class="text-muted small">Amount</label>
                <div class="fw-semibold">{{ number_format($payment->amount) }} TZS</div>
            </div>
            @if(is_array($payment->allocation) && count($payment->allocation))
            <div class="col-12">
                <label class="text-muted small">Allocation (fee split)</label>
                <ul class="mb-0 small">
                    @foreach($payment->allocation as $k => $v)
                        @if((float) $v > 0)
                            <li><strong>{{ $k }}</strong>: {{ number_format((float) $v) }} TZS</li>
                        @endif
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="col-md-4">
                <label class="text-muted small">Method</label>
                <div>{{ $payment->payment_method }}</div>
            </div>
            <div class="col-md-4">
                <label class="text-muted small">Date</label>
                <div>{{ $payment->paid_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="col-md-4">
                <label class="text-muted small">Control / reference no.</label>
                <div>{{ $payment->reference ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <label class="text-muted small">Received by</label>
                <div>{{ $payment->receiver->name ?? '—' }}</div>
            </div>
        </div>
        <hr class="my-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-receipt me-1"></i>Print receipt</a>
            <a href="{{ route('payments.reverse.form', $payment) }}" class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reverse payment</a>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Back to list</a>
        </div>
    </div>
</div>
@endsection
