@extends('layouts.app')
@section('title', 'Financial Statement')
@push('styles')
<style>
    .fin-stmt-info-table th { width: 38%; font-weight: 600; background: #f8fafc; }
    .fin-stmt-info-table td, .fin-stmt-info-table th { padding: .55rem .75rem; font-size: .875rem; vertical-align: middle; }
    .fin-stmt-year-title { text-align: center; font-weight: 700; font-size: 1rem; margin: 1.25rem 0 .75rem; color: #0f172a; }
    .fin-stmt-table thead th { font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: #475569; white-space: nowrap; background: #f1f5f9; }
    .fin-stmt-table td { font-size: .8125rem; vertical-align: middle; }
    .fin-stmt-table .text-money { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .fin-stmt-section-row td { background: #e2e8f0; font-weight: 700; font-size: .8125rem; color: #334155; }
    .fin-stmt-semester-row td { background: #dbeafe; font-weight: 700; font-size: .875rem; color: #1e3a8a; }
    .fin-stmt-semester-row .semester-year { font-weight: 500; color: #475569; font-size: .8125rem; }
    .fin-stmt-summary-row td { background: #f1f5f9; font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .fin-stmt-summary-row .text-money { color: #0f172a; font-size: .875rem; }
    .fin-stmt-print-link { font-size: .875rem; }
    @media print {
        .sidebar-wrap, .topbar, .student-breadcrumb, .fin-stmt-no-print, .alert, footer { display: none !important; }
        .main-wrap { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff; }
        .card-landing { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb fin-stmt-no-print">
    <a href="{{ route('dashboard') }}">Home</a>
    <span class="mx-2">/</span>
    <span>Financial Statement</span>
</nav>

<div class="alert alert-info d-flex align-items-start gap-2 mb-3">
    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
    <div class="small mb-0">Below is your financial information.</div>
</div>

<p class="fin-stmt-no-print mb-3">
    <a href="#" class="fin-stmt-print-link text-decoration-none" onclick="window.print(); return false;">
        <i class="bi bi-printer me-1"></i>Click here to print financial statement
    </a>
</p>

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

                    @php
                        $annual = $statement['annual_balance'];
                        $cumulative = $statement['cumulative_balance'];
                    @endphp
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
