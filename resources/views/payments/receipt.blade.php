<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt - {{ config('app.name') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --receipt-navy-1: #071d52;
            --receipt-navy-2: #1a4fb5;
            --receipt-ink: #0f172a;
            --receipt-muted: #64748b;
            --receipt-border: #e2e8f0;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #eef1f6;
            margin: 0;
            padding: 2rem 1rem;
            color: var(--receipt-ink);
        }
        .receipt-card {
            max-width: 460px;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        }
        .receipt-band {
            background: linear-gradient(135deg, var(--receipt-navy-1) 0%, var(--receipt-navy-2) 100%);
            color: #fff;
            padding: 1.5rem 1.5rem 1.25rem;
            text-align: center;
        }
        .receipt-band img { height: 46px; margin-bottom: .5rem; object-fit: contain; }
        .receipt-band h1 { margin: 0; font-size: 1.05rem; font-weight: 700; letter-spacing: .01em; }
        .receipt-band .sub { font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; opacity: .85; margin-top: .15rem; }
        .receipt-status {
            display: inline-flex; align-items: center; gap: .35rem;
            background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35);
            border-radius: 999px; padding: .2rem .75rem; font-size: .7rem; font-weight: 600;
            letter-spacing: .05em; text-transform: uppercase; margin-top: .6rem;
        }
        .receipt-body { padding: 1.4rem 1.5rem .5rem; }
        .receipt-meta { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        .receipt-meta td { padding: .3rem 0; font-size: .8125rem; vertical-align: top; }
        .receipt-meta td:first-child { color: var(--receipt-muted); width: 42%; }
        .receipt-meta td:last-child { font-weight: 600; text-align: right; }
        .receipt-divider { border: none; border-top: 1px dashed var(--receipt-border); margin: .5rem 0 1rem; }
        .receipt-items { width: 100%; border-collapse: collapse; margin-bottom: .25rem; }
        .receipt-items th {
            font-size: .65rem; text-transform: uppercase; letter-spacing: .06em; color: var(--receipt-muted);
            text-align: left; font-weight: 600; padding-bottom: .4rem; border-bottom: 1px solid var(--receipt-border);
        }
        .receipt-items th:last-child, .receipt-items td:last-child { text-align: right; }
        .receipt-items td { padding: .45rem 0; font-size: .8125rem; border-bottom: 1px solid #f1f5f9; }
        .receipt-items td.ref { color: var(--receipt-muted); font-size: .7rem; }
        .receipt-total-row td {
            padding-top: .75rem; font-size: .95rem; font-weight: 700; border-bottom: none;
        }
        .receipt-total-row .amount { color: var(--receipt-navy-2); font-variant-numeric: tabular-nums; }
        .receipt-footer {
            margin-top: 1.25rem; padding: 1rem 1.5rem 1.4rem; text-align: center;
            font-size: .75rem; color: var(--receipt-muted); border-top: 1px solid var(--receipt-border);
        }
        .receipt-footer strong { color: var(--receipt-ink); }
        .receipt-actions { max-width: 460px; margin: 1.25rem auto 0; text-align: center; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-card { box-shadow: none; border-radius: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @php
        $lines = [
            'tuition' => 'Tuition Fee',
            'nactvet_qa' => 'NACTVET QA',
            'nhif' => 'NHIF',
        ];
        $hasAnyLine = collect($lines)->keys()->contains(fn ($k) => $payment->allocatedAmount($k) > 0);
    @endphp
    <div class="receipt-card">
        <div class="receipt-band">
            @if(file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="">
            @endif
            <h1>{{ config('college.institution_name', config('app.name')) }}</h1>
            <div class="sub">Official Payment Receipt</div>
            <div class="receipt-status"><i class="bi bi-check-circle-fill"></i> Payment Confirmed</div>
        </div>
        <div class="receipt-body">
            <table class="receipt-meta">
                <tr><td>Receipt No.</td><td>#{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
                <tr><td>Date</td><td>{{ $payment->paid_at->format('d M Y, H:i') }}</td></tr>
                <tr><td>Student</td><td>{{ $payment->student->full_name }}</td></tr>
                <tr><td>Reg. No.</td><td>{{ $payment->student->reg_no }}</td></tr>
                @if($payment->student->programme?->code)
                <tr><td>Programme</td><td>{{ $payment->student->programme->code }}</td></tr>
                @endif
                @if($payment->academic_year)
                <tr><td>Academic session</td><td>{{ \App\Support\AcademicSession::label((int) $payment->academic_year) }}</td></tr>
                @endif
                <tr><td>Method</td><td>{{ ucfirst($payment->payment_method) }}</td></tr>
            </table>

            <hr class="receipt-divider">

            <table class="receipt-items">
                <thead>
                    <tr><th>Fee item</th><th>Amount (TZS)</th></tr>
                </thead>
                <tbody>
                    @if($hasAnyLine)
                        @foreach($lines as $key => $label)
                            @php $lineAmount = $payment->allocatedAmount($key); @endphp
                            @if($lineAmount > 0)
                            <tr>
                                <td>
                                    {{ $label }}
                                    @if($payment->componentReference($key))
                                    <div class="ref">Ref: {{ $payment->componentReference($key) }}</div>
                                    @endif
                                </td>
                                <td>{{ number_format($lineAmount, 0) }}</td>
                            </tr>
                            @endif
                        @endforeach
                    @else
                    <tr>
                        <td>
                            Payment
                            @if($payment->reference)
                            <div class="ref">Ref: {{ $payment->reference }}</div>
                            @endif
                        </td>
                        <td>{{ number_format($payment->amount, 0) }}</td>
                    </tr>
                    @endif
                    <tr class="receipt-total-row">
                        <td>Total paid</td>
                        <td class="amount">{{ number_format($payment->amount, 0) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="receipt-footer">
            Thank you for your payment, <strong>{{ $payment->student->full_name }}</strong>.<br>
            Generated on {{ now()->format('d M Y, H:i') }}
        </div>
    </div>
    <div class="receipt-actions no-print">
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print receipt</button>
        <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-secondary btn-sm ms-1">Back to payment</a>
    </div>
</body>
</html>
