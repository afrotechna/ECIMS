@extends('layouts.app')
@section('title', 'Financial Statement')
@push('styles')
<style>
    .fin-stmt-info-table th { width: 38%; font-weight: 600; background: #f8fafc; }
    .fin-stmt-info-table td, .fin-stmt-info-table th { padding: .55rem .75rem; font-size: .875rem; vertical-align: middle; }
    .fin-stmt-categories { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .fin-stmt-categories .badge { font-weight: 600; font-size: .75rem; padding: .45rem .8rem; border-radius: 999px; }
    .fin-stmt-summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .fin-stmt-stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .65rem; padding: .9rem 1rem; }
    .fin-stmt-stat .label { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; margin-bottom: .3rem; }
    .fin-stmt-stat .value { font-size: 1.15rem; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .fin-stmt-stat.is-balanced .value { color: #16a34a; }
    .fin-stmt-stat.is-owing .value { color: #dc2626; }
    .fin-stmt-year-title { text-align: center; font-weight: 700; font-size: 1rem; margin: 1.25rem 0 .75rem; color: #0f172a; }
    .fin-stmt-table thead th { font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: #475569; white-space: nowrap; background: #f1f5f9; }
    .fin-stmt-table td { font-size: .8125rem; vertical-align: middle; }
    .fin-stmt-table .text-money { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .fin-stmt-section-row td { background: #e2e8f0; font-weight: 700; font-size: .8125rem; color: #334155; }
    .fin-stmt-semester-row td { background: var(--cohas-gradient, linear-gradient(135deg, #071d52 0%, #1a4fb5 100%)); font-weight: 700; font-size: .875rem; color: #fff; }
    .fin-stmt-semester-row .semester-year { font-weight: 500; color: rgba(255,255,255,.8); font-size: .8125rem; }
    .fin-stmt-summary-row td { background: #f1f5f9; font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .fin-stmt-summary-row .text-money { color: #0f172a; font-size: .875rem; }
    .fin-stmt-print-link { font-size: .875rem; }
    @media print {
        .sidebar-wrap, .topbar, .student-breadcrumb, .fin-stmt-no-print, .alert, footer { display: none !important; }
        .main-wrap { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff; }
        .card-landing { box-shadow: none !important; border: 1px solid #ddd !important; }
        .page-header-landing { background: #fff !important; color: #0f172a !important; border: 1px solid #ddd; }
        .fin-stmt-semester-row td { background: #e2e8f0 !important; color: #0f172a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb fin-stmt-no-print">
    <a href="{{ route('dashboard') }}">Home</a>
    <span class="mx-2">/</span>
    <span>Financial Statement</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 fin-stmt-no-print">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-receipt-cutoff me-2 opacity-90"></i>Financial Statement</h1>
        <p class="page-subtitle-landing mb-0">{{ $student->full_name }} · {{ $student->reg_no }}</p>
    </div>
    <a href="#" class="btn btn-light btn-sm text-dark" onclick="window.print(); return false;">
        <i class="bi bi-printer me-1"></i>Print statement
    </a>
</div>

<div class="fin-stmt-categories">
    <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-mortarboard me-1"></i>Tuition Fee</span>
    <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-patch-check me-1"></i>NACTVET QA</span>
    <span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-heart-pulse me-1"></i>NHIF</span>
</div>

<div class="card card-landing mb-3">
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 fin-stmt-info-table">
            <tbody>
                <tr>
                    <th>Full Name</th>
                    <td>{{ $student->full_name }}</td>
                </tr>
                <tr>
                    <th>Registration Number</th>
                    <td>{{ $student->reg_no }}</td>
                </tr>
                <tr>
                    <th>Programme</th>
                    <td>{{ $student->programme->name ?? '—' }}@if($student->programme?->code) ({{ $student->programme->code }})@endif</td>
                </tr>
                <tr>
                    <th>College / Institute / School</th>
                    <td>{{ config('college.school_name', config('college.institution_name')) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="fin-stmt-no-print mb-3">
    <form method="GET" action="{{ route('students.ledger', $student) }}" class="row g-2 align-items-end">
        <div class="col-auto">
            <label for="academic_year" class="form-label small mb-0">Financial year</label>
            <select name="academic_year" id="academic_year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($availableYears as $year)
                <option value="{{ $year }}" {{ (int) $academicYear === (int) $year ? 'selected' : '' }}>{{ $year }}/{{ $year + 1 }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

@php
    $annual = $statement['annual_balance'];
    $cumulative = $statement['cumulative_balance'];
    $cumBalance = $cumulative['balance'];
@endphp
<div class="fin-stmt-summary-cards">
    <div class="fin-stmt-stat">
        <div class="label">Total billed</div>
        <div class="value">{{ number_format($cumulative['fee'], 0) }}</div>
    </div>
    <div class="fin-stmt-stat">
        <div class="label">Total paid</div>
        <div class="value">{{ number_format($cumulative['payment'], 0) }}</div>
    </div>
    <div class="fin-stmt-stat {{ $cumBalance !== null && $cumBalance <= 0 ? 'is-balanced' : ($cumBalance !== null ? 'is-owing' : '') }}">
        <div class="label">{{ $cumBalance !== null && $cumBalance <= 0 ? 'Fully cleared' : 'Outstanding balance' }}</div>
        <div class="value">{{ $cumBalance !== null ? number_format($cumBalance, 0) : '—' }}</div>
    </div>
</div>

<div class="card card-landing">
    <div class="card-body">
        <div class="fin-stmt-year-title">Financial Year: {{ $statement['academic_year_label'] }}</div>

        <div class="table-responsive">
            <table class="table table-bordered fin-stmt-table mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width:3rem">S/No</th>
                        <th>Date</th>
                        <th>Transaction Type</th>
                        <th>Payment Type</th>
                        <th>Remark</th>
                        <th>Reference No.</th>
                        <th class="text-money">Fee</th>
                        <th class="text-money">Payment</th>
                        <th class="text-money">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statement['semesters'] as $semesterBlock)
                    <tr class="fin-stmt-semester-row">
                        <td colspan="9">
                            {{ $semesterBlock['title'] }}
                            <span class="semester-year">— {{ $semesterBlock['academic_year_label'] }}</span>
                        </td>
                    </tr>
                    @if(empty($semesterBlock['sections']))
                    <tr>
                        <td colspan="9" class="text-center text-muted py-3">No transactions for this semester.</td>
                    </tr>
                    @endif
                    @foreach($semesterBlock['sections'] as $section)
                    <tr class="fin-stmt-section-row">
                        <td colspan="9">{{ $section['title'] }}</td>
                    </tr>
                    @foreach($section['rows'] as $row)
                    <tr>
                        <td class="text-center text-muted">{{ $row['sn'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['transaction_type'] }}</td>
                        <td>{{ $row['payment_type'] }}</td>
                        <td>{{ $row['remark'] }}</td>
                        <td class="small">{{ $row['reference_no'] }}</td>
                        <td class="text-money">{{ $row['fee'] !== null ? number_format($row['fee'], 2) : '—' }}</td>
                        <td class="text-money">{{ $row['payment'] !== null ? number_format($row['payment'], 2) : '—' }}</td>
                        <td class="text-money fw-semibold">{{ $row['balance'] !== null ? number_format($row['balance'], 2) : '—' }}</td>
                    </tr>
                    @endforeach
                    @endforeach
                    @endforeach

                    <tr class="fin-stmt-summary-row">
                        <td colspan="6" class="text-center">Annual Balance</td>
                        <td class="text-money">—</td>
                        <td class="text-money">—</td>
                        <td class="text-money">{{ $annual['balance'] !== null ? number_format($annual['balance'], 2) : '—' }}</td>
                    </tr>
                    <tr class="fin-stmt-summary-row">
                        <td colspan="6" class="text-center">Cumulative Balance</td>
                        <td class="text-money">{{ number_format($cumulative['fee'], 2) }}</td>
                        <td class="text-money">{{ number_format($cumulative['payment'], 2) }}</td>
                        <td class="text-money">{{ $cumulative['balance'] !== null ? number_format($cumulative['balance'], 2) : '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
