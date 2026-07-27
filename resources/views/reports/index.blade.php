@extends('layouts.app')
@section('title', 'Reports')
@push('styles')
<style>
    .fin-report-link {
        display: block; text-decoration: none; color: inherit; height: 100%;
        border: 1px solid #e2e8f0; border-radius: .75rem; background: #fff; overflow: hidden;
        transition: box-shadow .2s, border-color .2s;
    }
    .fin-report-link:hover { border-color: #0d3651; box-shadow: 0 4px 14px rgba(10, 22, 40, .1); color: inherit; }
    .fin-report-link-head {
        padding: .85rem 1rem; background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%);
        color: #fff; font-weight: 600; font-size: .9rem;
    }
    .fin-report-link-body { padding: 1rem; }
    .fin-report-link-desc { font-size: .8125rem; color: #64748b; margin: 0; }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Finance</span>
    <span class="mx-2">/</span>
    <span>Reports</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-graph-up me-2 opacity-90"></i>Reports</h1>
    <p class="page-subtitle-landing mb-0">Financial summaries (fees and cash), plus academic and registry exports—grouped below for clarity.</p>
</div>

<h2 class="h6 text-uppercase text-muted fw-bold mb-3">Fees &amp; payments</h2>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('reports.income') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-cash-stack me-2"></i>Income report</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">Cash by date range, method breakdown, CSV export.</p></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('reports.arrears') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-exclamation-triangle me-2"></i>Arrears</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">Students with outstanding balances.</p></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('reports.payment-by-programme') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-currency-exchange me-2"></i>By programme</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">Collections per programme for any period.</p></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('reports.fee-collection-summary') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-pie-chart me-2"></i>Fee collection</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">Expected vs collected per academic year.</p></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('payments.index') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-clock-history me-2"></i>Payment history</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">All receipts with ledger links.</p></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('reports.enrollment') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-people me-2"></i>Enrollment</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">Active students by programme or intake.</p></div>
        </a>
    </div>
</div>

<h2 class="h6 text-uppercase text-muted fw-bold mb-3">Academic &amp; registry</h2>
<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('reports.class-list') }}" class="card card-landing text-decoration-none text-dark h-100">
            <div class="card-header-landing"><i class="bi bi-journal-text me-2"></i>Class List</div>
            <div class="card-body d-flex align-items-center">
                <p class="text-muted small mb-0">Students per course and semester (exam list)</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing"><i class="bi bi-clipboard2-data me-2"></i>Admission Control Sheet</div>
            <div class="card-body d-flex flex-column">
                <p class="text-muted small mb-3">Students admission control sheet (all columns). View, print or export CSV.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('reports.admission-control-sheet') }}" class="btn btn-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
                    <a href="{{ route('reports.admission-control-sheet.export') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export full control sheet (CSV)</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.students-on-leave') }}" class="card card-landing text-decoration-none text-dark h-100">
            <div class="card-header-landing"><i class="bi bi-calendar-x me-2"></i>Students on leave</div>
            <div class="card-body d-flex align-items-center">
                <p class="text-muted small mb-0">Approved leave of absence (current)</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.academic-standing') }}" class="card card-landing text-decoration-none text-dark h-100">
            <div class="card-header-landing"><i class="bi bi-award me-2"></i>Academic standing</div>
            <div class="card-body d-flex align-items-center">
                <p class="text-muted small mb-0">Good standing, probation, repeat year</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.graduation-clearance') }}" class="card card-landing text-decoration-none text-dark h-100">
            <div class="card-header-landing"><i class="bi bi-clipboard-check me-2"></i>Graduation clearance</div>
            <div class="card-body d-flex align-items-center">
                <p class="text-muted small mb-0">Library, finance, accommodation, academic</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.nactvet-hub') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-building me-2"></i>NACTVET reporting pack</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">Students, results, graduates — CSV exports.</p></div>
        </a>
    </div>
</div>
@endsection
