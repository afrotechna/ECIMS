<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt - {{ config('app.name') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        body { font-family: system-ui, sans-serif; max-width: 400px; margin: 2rem auto; padding: 1rem; }
        .receipt-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 1rem; margin-bottom: 1rem; }
        .receipt-header h1 { margin: 0; font-size: 1.25rem; }
        .receipt-row { display: flex; justify-content: space-between; padding: .35rem 0; }
        .receipt-footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #ccc; font-size: .875rem; color: #666; text-align: center; }
        @media print { body { margin: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="receipt-header">
        <h1>{{ config('app.name') }}</h1>
        <div>PAYMENT RECEIPT</div>
    </div>
    <div class="receipt-row"><span>Receipt No:</span><strong>#{{ $payment->id }}</strong></div>
    <div class="receipt-row"><span>Date:</span><span>{{ $payment->paid_at->format('d/m/Y H:i') }}</span></div>
    <div class="receipt-row"><span>Student:</span><span>{{ $payment->student->full_name }}</span></div>
    <div class="receipt-row"><span>Reg. No:</span><span>{{ $payment->student->reg_no }}</span></div>
    @if($payment->academic_year)
    <div class="receipt-row"><span>Academic session:</span><span>{{ \App\Support\AcademicSession::label((int) $payment->academic_year) }}</span></div>
    @endif
    <div class="receipt-row"><span>Amount:</span><strong>{{ number_format($payment->amount, 0) }} TZS</strong></div>
    <div class="receipt-row"><span>Method:</span><span>{{ ucfirst($payment->payment_method) }}</span></div>
    <div class="receipt-row"><span>Control / ref:</span><span>{{ $payment->reference ?? '-' }}</span></div>
    <div class="receipt-footer">
        Thank you for your payment.<br>
        Generated on {{ now()->format('d/m/Y H:i') }}
    </div>
    <p class="no-print" style="margin-top: 1.5rem;">
        <button type="button" onclick="window.print()" class="btn btn-primary">Print receipt</button><br>
        <a href="{{ route('payments.show', $payment) }}" class="mt-2 d-inline-block">Back to payment</a>
    </p>
</body>
</html>
