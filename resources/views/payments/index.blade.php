@extends('layouts.app')

@section('title', 'Payment history')

@push('styles')
@include('reports.partials.finance-report-styles')
<style>
    .fin-student-hero {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        padding: 1.1rem 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(10, 22, 40, .06);
    }
    .fin-student-hero-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d3651, #1e4a7a);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .fin-student-hero-name { font-size: 1.15rem; font-weight: 800; color: #0f172a; }
    .fin-student-hero-meta { font-size: .8125rem; color: #64748b; }
    .pay-breakdown-group + .pay-breakdown-group { margin-top: 1rem; }
    .pay-breakdown-group-title {
        font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700;
        color: #64748b; margin-bottom: .5rem;
    }
    .pay-breakdown-row {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        padding: .55rem 0; border-bottom: 1px solid #f1f5f9; font-size: .875rem;
    }
    .pay-breakdown-row .fee-label { display: flex; align-items: center; gap: .5rem; font-weight: 600; color: #334155; }
    .pay-breakdown-row .fee-label i { color: #94a3b8; }
    .pay-breakdown-row .fee-amount { font-weight: 700; font-variant-numeric: tabular-nums; color: #0f172a; }
    .pay-breakdown-row.total { border-top: 2px solid #e2e8f0; border-bottom: none; margin-top: .5rem; padding-top: .75rem; }
    .pay-breakdown-row.total .fee-label { color: #0d3651; font-size: .9375rem; }
    .pay-breakdown-row.total .fee-amount { color: #0d3651; font-size: 1rem; }
    .pay-breakdown-details { border: 1px dashed #e2e8f0; border-radius: .5rem; padding: .6rem .8rem; }
    .pay-breakdown-details .label {
        font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; color: #64748b;
    }
    .pay-year-card .card-header-landing { background: #eef4fb; }
</style>
@endpush

@section('content')
@if($student ?? null)
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.index') }}">Students</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a>
    <span class="mx-2">/</span>
    <span>Payment history</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-clock-history me-2 opacity-90"></i>Payment history</h1>
        <p class="page-subtitle-landing mb-0">Every receipt recorded for this student.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('students.ledger', $student) }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-wallet2 me-1"></i> Ledger</a>
        <a href="{{ route('students.show', $student) }}" class="btn btn-outline-light btn-sm"><i class="bi bi-person me-1"></i> Profile</a>
    </div>
</div>

<div class="fin-student-hero">
    <div class="fin-student-hero-avatar">{{ strtoupper(substr($student->first_name ?? '?', 0, 1).substr($student->last_name ?? '', 0, 1)) }}</div>
    <div class="flex-grow-1">
        <div class="fin-student-hero-name">{{ $student->full_name }}</div>
        <div class="fin-student-hero-meta">
            @if($student->programme) {{ $student->programme->name }} ({{ $student->programme->code }}) @endif
        </div>
    </div>
</div>

<div class="fin-kpi-row">
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--green"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="fin-kpi-label">Total paid</div>
            <div class="fin-kpi-value">{{ number_format($studentTotal ?? 0) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--navy"><i class="bi bi-calendar-month"></i></div>
        <div>
            <div class="fin-kpi-label">{{ \App\Support\AcademicSession::label((int) ($currentYear ?? 0)) }}</div>
            <div class="fin-kpi-value">{{ number_format($studentYearTotal ?? 0) }} <span class="fs-6 fw-semibold text-muted">TZS</span></div>
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-icon fin-kpi-icon--amber"><i class="bi bi-receipt"></i></div>
        <div>
            <div class="fin-kpi-label">Receipts</div>
            <div class="fin-kpi-value">{{ number_format($studentReceiptCount ?? 0) }}</div>
        </div>
    </div>
</div>

@php
    $feeIcons = ['sem1_tuition' => 'bi-mortarboard', 'sem1_nhif' => 'bi-heart-pulse', 'sem1_nactvet_qa' => 'bi-patch-check', 'sem2_tuition' => 'bi-mortarboard'];
    $feeRowLabels = ['sem1_tuition' => 'Tuition Fee', 'sem1_nhif' => 'NHIF', 'sem1_nactvet_qa' => 'NACTVET QA', 'sem2_tuition' => 'Tuition Fee'];
@endphp
<div class="d-flex flex-column gap-3 mb-3">
    @forelse(($yearGroups ?? []) as $grp)
    <div class="card card-landing pay-year-card">
        <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                <i class="bi bi-calendar-month me-2"></i>{{ \App\Support\AcademicSession::label($grp['year']) }}
                @if($grp['label'])
                <span class="badge bg-light text-dark border ms-2">{{ $grp['label'] }}</span>
                @endif
            </span>
            <span class="fs-5 fw-bold" style="color:#0d3651;">{{ number_format($grp['total']) }} <span class="fs-6 fw-semibold text-muted">TZS</span></span>
        </div>
        <div class="card-body">
            @php
                $sem1TuitionPayment = $grp['semOne']->first();
                $sem2TuitionPayment = $grp['semTwo']->first();
                $sem1TuitionRef = $sem1TuitionPayment?->componentReference('tuition') ?: $sem1TuitionPayment?->reference;
                $sem2TuitionRef = $sem2TuitionPayment?->componentReference('tuition') ?: $sem2TuitionPayment?->reference;
            @endphp
            @if($sem1TuitionRef || $sem2TuitionRef)
            <div class="pay-breakdown-details mb-3">
                <div class="label mb-1">Tuition control numbers</div>
                @if($sem1TuitionRef)
                <div class="small text-muted">Semester I: <code>{{ $sem1TuitionRef }}</code></div>
                @endif
                @if($sem2TuitionRef)
                <div class="small text-muted">Semester II: <code>{{ $sem2TuitionRef }}</code></div>
                @endif
            </div>
            @endif
            @foreach(['semOne' => ['Semester I', ['sem1_tuition', 'sem1_nhif', 'sem1_nactvet_qa']], 'semTwo' => ['Semester II', ['sem2_tuition']]] as $semKey => $semMeta)
                @php [$semTitle, $rowKeys] = $semMeta; $semPayments = $grp[$semKey]; @endphp
                @if($semPayments->isNotEmpty())
                <div class="pay-breakdown-group">
                    <div class="pay-breakdown-group-title d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>{{ $semTitle }}</span>
                        <span class="d-flex flex-wrap gap-2 align-items-center">
                            @foreach($semPayments as $p)
                            <span class="small text-muted text-lowercase">{{ $p->paid_at->format('d/m/Y H:i') }} &middot; {{ \App\Models\Payment::methods()[$p->payment_method] ?? $p->payment_method }} &middot; <code>{{ $p->reference ?: $p->id }}</code></span>
                            @include('partials.action-view', ['href' => route('payments.show', $p)])
                            <a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Receipt</a>
                            @endforeach
                        </span>
                    </div>
                    @foreach($rowKeys as $key)
                        @if(($grp['breakdown'][$key] ?? 0) > 0)
                        <div class="pay-breakdown-row">
                            <span class="fee-label"><i class="bi {{ $feeIcons[$key] }}"></i>{{ $feeRowLabels[$key] }}</span>
                            <span class="fee-amount">{{ number_format($grp['breakdown'][$key]) }} TZS</span>
                        </div>
                        @endif
                    @endforeach
                </div>
                @endif
            @endforeach
            <div class="pay-breakdown-row total">
                <span class="fee-label">Grand total</span>
                <span class="fee-amount">{{ number_format($grp['total']) }} TZS</span>
            </div>
        </div>
    </div>
    @empty
    <div class="card card-landing"><div class="card-body text-center text-muted py-5">No payments recorded yet.</div></div>
    @endforelse
</div>

@else
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
        <p class="page-subtitle-landing mb-0">All recorded fee payments.</p>
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

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-0">Academic year</label>
                <select name="academic_year" class="form-select form-select-sm" data-no-search>
                    <option value="" {{ $selectedAcademicYear === null ? 'selected' : '' }}>All</option>
                    @foreach($academicYearOptions as $start => $label)
                    <option value="{{ $start }}" {{ (string) $selectedAcademicYear === (string) $start ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-0">Semester</label>
                <select name="semester" class="form-select form-select-sm" data-no-search>
                    <option value="both" {{ $selectedSemester === 'both' ? 'selected' : '' }}>Semester I &amp; II</option>
                    <option value="one" {{ $selectedSemester === 'one' ? 'selected' : '' }}>Semester I</option>
                    <option value="two" {{ $selectedSemester === 'two' ? 'selected' : '' }}>Semester II</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['academic_year', 'semester']))
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
        <span><i class="bi bi-list-ul me-2"></i>All payments</span>
        <span class="badge bg-light text-dark">{{ $paymentGroups->total() }} payment records</span>
    </div>
    <div class="card-body p-0 fin-pay-table-only">
        <div class="table-responsive">
        <table class="table fin-report-table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th class="fin-money">Amount</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentGroups as $grp)
                    @php
                        $groupPayments = $grp['semOne']->merge($grp['semTwo'])->sortByDesc('paid_at')->values();
                        $latest = $groupPayments->first();
                    @endphp
                <tr>
                    <td>
                        <span class="d-block fw-semibold">{{ $grp['lastActivity']->format('d/m/Y') }}</span>
                        <span class="small text-muted">{{ $grp['lastActivity']->format('H:i') }}</span>
                    </td>
                    <td>
                        <span class="d-block fw-semibold">{{ $grp['student']?->full_name ?? '—' }}</span>
                    </td>
                    <td class="fin-money fin-money--lg">{{ number_format($grp['total']) }}</td>
                    <td class="text-end text-nowrap">
                        @include('partials.action-view', ['href' => route('payments.show', $latest)])
                        <a href="{{ route('payments.receipt', $latest) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Receipt" aria-label="Receipt"><i class="bi bi-receipt" aria-hidden="true"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">No payments recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="card-body fin-pay-cards-only">
        @forelse($paymentGroups as $grp)
            @php
                $groupPaymentsCard = $grp['semOne']->merge($grp['semTwo'])->sortByDesc('paid_at')->values();
                $latestCard = $groupPaymentsCard->first();
            @endphp
        <article class="fin-pay-card">
            <div class="fin-pay-card-head">
                <div>
                    <div class="fw-semibold">{{ $grp['student']?->full_name ?? '—' }}</div>
                    <div class="small text-muted">{{ $grp['lastActivity']->format('d/m/Y H:i') }}</div>
                </div>
                <div class="fin-pay-card-amount">{{ number_format($grp['total']) }} TZS</div>
            </div>
            <div class="d-flex gap-2 mt-2">
                @include('partials.action-view', ['href' => route('payments.show', $latestCard)])
                <a href="{{ route('payments.receipt', $latestCard) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Receipt" aria-label="Receipt"><i class="bi bi-receipt" aria-hidden="true"></i></a>
            </div>
        </article>
        @empty
        <p class="text-center text-muted py-4 mb-0">No payments recorded yet.</p>
        @endforelse
    </div>
    @if($paymentGroups->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $paymentGroups->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endif
@endsection
