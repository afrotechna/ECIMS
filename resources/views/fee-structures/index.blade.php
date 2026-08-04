@extends('layouts.app')

@section('title', 'Fees')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Finance</span>
    <span class="mx-2">/</span>
    <span>Fees</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-currency-exchange me-2 opacity-90"></i>Fees</h1>
        <p class="page-subtitle-landing mb-0">Programme and session fee schedules with semester breakdown.</p>
    </div>
    <a href="{{ route('fee-structures.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add</a>
</div>

@push('styles')
<style>
    .fee-schedule-list { display: flex; flex-direction: column; gap: 1rem; }
    .fee-schedule-item {
        border: 1px solid #e2e8f0;
        border-radius: .5rem;
        background: #fff;
        overflow: hidden;
    }
    .fee-schedule-item-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .fee-schedule-item-title { font-weight: 700; color: #0f172a; margin: 0; font-size: 1rem; }
    .fee-schedule-item-meta { font-size: .8125rem; color: #64748b; margin: 0; }
    .fee-sem-block {
        padding: .75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .fee-sem-block:last-of-type { border-bottom: none; }
    .fee-sem-block--one { background: #f8fbff; }
    .fee-sem-block--two { background: #f7fdf9; }
    .fee-sem-block--annual { background: #fffbeb; }
    .fee-sem-block-title {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #475569;
        margin-bottom: .5rem;
    }
    .fee-sem-lines {
        display: grid;
        grid-template-columns: 1fr;
        gap: .35rem .75rem;
        margin: 0;
    }
    @media (min-width: 480px) {
        .fee-sem-lines { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .fee-sem-line {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: .5rem;
        font-size: .8125rem;
        margin: 0;
    }
    .fee-sem-line dt { color: #64748b; font-weight: 500; margin: 0; min-width: 0; }
    .fee-sem-line dd { margin: 0; font-weight: 600; color: #0f172a; font-variant-numeric: tabular-nums; text-align: right; flex-shrink: 0; }
    .fee-sem-line--subtotal dt,
    .fee-sem-line--subtotal dd { font-weight: 700; color: #0f172a; }
    .fee-schedule-footnote {
        font-size: .75rem;
        color: #64748b;
        padding: .65rem 1rem;
        margin: 0;
        border-top: 1px solid #e2e8f0;
        background: #fafafa;
    }
</style>
@endpush

@php
    $bulkDelete = [
        'bulkModule' => 'finance_fees',
        'bulkAction' => route('fee-structures.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteFeeStructures',
        'bulkScopeId' => 'feeStructuresList',
        'bulkItemCount' => $structures->count(),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Schedules</span>
        <div class="d-flex align-items-center gap-2 no-print">
            @canModule('finance_fees', 'delete')
                @if($structures->count() > 0)
                    <input type="checkbox" class="form-check-input bulk-delete-select-all" data-bulk-scope="feeStructuresList" aria-label="Select all schedules on this page">
                    <span class="small text-muted">Select all</span>
                @endif
            @endcanModule
            @include('partials.bulk-delete.toolbar', $bulkDelete)
        </div>
    </div>
    <div class="card-body" id="feeStructuresList" data-bulk-delete-scope>
        @forelse($structures as $s)
        @php
            $totals = $s->semesterTotals();
            $bs1 = $s->feeStructureSemesters->firstWhere('semester_number', 1);
            $bs2 = $s->feeStructureSemesters->firstWhere('semester_number', 2);
        @endphp
        <article class="fee-schedule-item {{ ! $loop->last ? 'mb-3' : '' }}">
            <header class="fee-schedule-item-head">
                @include('partials.bulk-delete.checkbox-inline', array_merge($bulkDelete, ['bulkRowId' => $s->id]))
                <div>
                    <h2 class="fee-schedule-item-title">{{ \App\Support\AcademicSession::label((int) $s->academic_year) }}</h2>
                    <p class="fee-schedule-item-meta mb-0">{{ $s->programme ? $s->programme->code.' — '.$s->programme->name : 'All programmes' }}</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($s->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                    @include('partials.action-edit', ['href' => route('fee-structures.edit', $s), 'class' => 'me-1', 'iconOnly' => true])
                    @canModule('finance_fees', 'delete')
                    <form action="{{ route('fee-structures.destroy', $s) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        @include('partials.action-delete', ['swalTitle' => 'Delete this schedule?', 'swalText' => 'This cannot be undone.'])
                    </form>
                    @endcanModule
                </div>
            </header>

            <section class="fee-sem-block fee-sem-block--one">
                <div class="fee-sem-block-title">Semester One</div>
                <dl class="fee-sem-lines">
                    <div class="fee-sem-line">
                        <dt>Tuition</dt>
                        <dd>{{ number_format($totals['semester_one']['tuition']) }} TZS</dd>
                    </div>
                    <div class="fee-sem-line">
                        <dt>NHIF</dt>
                        <dd>{{ number_format($totals['semester_one']['nhif']) }} TZS</dd>
                    </div>
                    <div class="fee-sem-line">
                        <dt>NACTVET QA</dt>
                        <dd>{{ number_format($totals['semester_one']['nactvet_qa']) }} TZS</dd>
                    </div>
                    <div class="fee-sem-line fee-sem-line--subtotal">
                        <dt>Subtotal</dt>
                        <dd>{{ number_format($totals['semester_one']['subtotal']) }} TZS</dd>
                    </div>
                </dl>
                @if($bs1 && $bs1->breakdownTotal() > 0)
                <p class="small text-muted mb-0 mt-2">
                    Tuition includes:
                    @foreach(array_filter(['Internal exams' => $bs1->internal_exam, 'Registration' => $bs1->registration, 'Games' => $bs1->games, 'Emergency fund' => $bs1->emergency_fund, 'Practicum guide' => $bs1->practicum_guide]) as $label => $amt)
                        {{ $label }} {{ number_format($amt) }}@if(! $loop->last), @endif
                    @endforeach
                </p>
                @endif
            </section>

            <section class="fee-sem-block fee-sem-block--two">
                <div class="fee-sem-block-title">Semester Two</div>
                <dl class="fee-sem-lines">
                    <div class="fee-sem-line">
                        <dt>Tuition (continuing)</dt>
                        <dd>{{ number_format($totals['semester_two']['tuition_continuous']) }} TZS</dd>
                    </div>
                    <div class="fee-sem-line">
                        <dt>Tuition (repeat / transfer)</dt>
                        <dd>{{ number_format($totals['semester_two']['tuition_repeat_transfer']) }} TZS</dd>
                    </div>
                    <div class="fee-sem-line fee-sem-line--subtotal">
                        <dt>Subtotal (continuing)</dt>
                        <dd>{{ number_format($totals['semester_two']['subtotal']) }} TZS</dd>
                    </div>
                </dl>
                @if($bs2 && (float) $bs2->internal_exam > 0)
                <p class="small text-muted mb-0 mt-2">Tuition includes: Internal exams {{ number_format($bs2->internal_exam) }}</p>
                @endif
            </section>

            <section class="fee-sem-block fee-sem-block--annual">
                <div class="fee-sem-block-title">Annual summary</div>
                <dl class="fee-sem-lines">
                    <div class="fee-sem-line">
                        <dt>Accommodation</dt>
                        <dd>{{ number_format($totals['accommodation']) }} TZS</dd>
                    </div>
                    <div class="fee-sem-line">
                        <dt>Other charges</dt>
                        <dd>{{ number_format($totals['other_charges']) }} TZS</dd>
                    </div>
                    @if((float) $s->national_exam_fee > 0)
                    <div class="fee-sem-line">
                        <dt>— incl. national exam fee</dt>
                        <dd>{{ number_format($s->national_exam_fee) }} TZS</dd>
                    </div>
                    @endif
                    <div class="fee-sem-line fee-sem-line--subtotal">
                        <dt>Annual total</dt>
                        <dd>{{ number_format($totals['annual_total']) }} TZS</dd>
                    </div>
                </dl>
            </section>
        </article>
        @empty
        <p class="text-center text-muted py-5 mb-0">No schedules yet. <a href="{{ route('fee-structures.create') }}">Add</a></p>
        @endforelse

        @if($structures->isNotEmpty())
        <p class="fee-schedule-footnote mb-0 mt-3 rounded">
            <strong>Annual total</strong> = Semester One + Semester Two (continuing) + accommodation + other.
            Repeat / transfer students pay the Semester Two repeat tuition instead of continuing.
        </p>
        @endif
    </div>
    @if($structures->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $structures->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
