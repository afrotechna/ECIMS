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
    <h1 class="page-title-landing"><i class="bi bi-graph-up me-2 opacity-90"></i>Financial Reports</h1>
    <p class="page-subtitle-landing mb-0">Fees, cash collection and enrollment — reports that support finance. Academic and registrar reports now live under their own modules in the sidebar.</p>
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
    @canModule('finance_payments', 'view')
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('payments.index') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-clock-history me-2"></i>Payment history</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">All receipts with ledger links.</p></div>
        </a>
    </div>
    @endcanModule
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('reports.enrollment') }}" class="fin-report-link">
            <div class="fin-report-link-head"><i class="bi bi-people me-2"></i>Enrollment</div>
            <div class="fin-report-link-body"><p class="fin-report-link-desc">Active students by programme or intake.</p></div>
        </a>
    </div>
</div>
@endsection
