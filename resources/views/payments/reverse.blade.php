@extends('layouts.app')
@section('title', 'Reverse Payment')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('payments.index') }}">Payment history</a>
    <span class="mx-2">/</span>
    <a href="{{ route('payments.show', $payment) }}">#{{ $payment->id }}</a>
    <span class="mx-2">/</span>
    <span>Reverse</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-arrow-counterclockwise me-2 opacity-90"></i>Reverse Payment</h1>
    <p class="page-subtitle-landing mb-0">This will add a debit to the student ledger. The original payment record is kept.</p>
</div>
<div class="card card-landing">
    <div class="card-header-landing">Confirm reversal</div>
    <div class="card-body">
        <p class="mb-3">Payment #{{ $payment->id }}: <strong>{{ number_format($payment->amount) }} TZS</strong> for {{ $payment->student->full_name }} ({{ $payment->student->reg_no }}) on {{ $payment->paid_at->format('d/m/Y') }}.</p>
        <form action="{{ route('payments.reverse', $payment) }}" method="POST" onsubmit="return confirm('Reverse this payment? A debit entry will be added to the student ledger.');">
            @csrf
            <div class="mb-3">
                <label for="reason" class="form-label">Reason (optional)</label>
                <input type="text" class="form-control" id="reason" name="reason" value="{{ old('reason') }}" placeholder="e.g. Duplicate entry">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">Reverse payment</button>
                <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
