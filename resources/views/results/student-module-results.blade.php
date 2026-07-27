@extends('layouts.app')
@section('title', 'My Modules Result')
@push('styles')
<style>
    .module-result-remarks-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem 2rem; margin-bottom: 1.25rem; font-size: .8125rem; color: #475569; }
    .module-result-remarks-legend span { display: inline-flex; align-items: center; gap: .45rem; }
    .module-result-swatch { width: 1.1rem; height: 1.1rem; border-radius: 2px; border: 1px solid rgba(15, 23, 42, .12); }
    .module-result-swatch.pass { background: #bbf7d0; }
    .module-result-swatch.failed { background: #fecdd3; }
    .module-result-swatch.incomplete { background: #bfdbfe; }
    .module-results-table thead th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; white-space: nowrap; }
    .module-results-table td { vertical-align: middle; font-size: .875rem; }
    .module-remark-cell { font-weight: 600; text-align: center; }
    .module-remark-cell.pass { background: #bbf7d0; color: #14532d; }
    .module-remark-cell.failed { background: #fecdd3; color: #9f1239; }
    .module-remark-cell.incomplete { background: #bfdbfe; color: #1e3a8a; }
    .year-section-subtitle { font-size: .8125rem; color: #64748b; font-weight: 500; margin-top: .15rem; }
    .year-result-summary { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .year-result-summary h3 { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; margin-bottom: .75rem; }
    .year-summary-stat { text-align: center; padding: .5rem; }
    .year-summary-stat .label { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; margin-bottom: .25rem; }
    .year-summary-stat .value { font-size: 1.125rem; font-weight: 700; color: #0f172a; }
    .semester-nested-card { border: 1px solid #e2e8f0; border-radius: .375rem; margin-bottom: .75rem; overflow: hidden; }
    .semester-nested-card:last-child { margin-bottom: 0; }
    .module-results-accordion .courses-fold-trigger { cursor: pointer; user-select: none; }
    .module-results-accordion .courses-fold-icon { transition: transform 0.2s ease; display: inline-block; }
    .module-results-accordion .courses-fold-trigger.collapsed .courses-fold-icon { transform: rotate(-90deg); }
    .semester-sem-summary { font-size: .8125rem; color: #475569; padding: .5rem 1rem; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Home</a>
    <span class="mx-2">/</span>
    <span>My Modules Result</span>
</nav>

<div class="page-header-landing mb-3">
    <h1 class="page-title-landing"><i class="bi bi-award me-2 opacity-90"></i>My Modules Result</h1>
    <p class="page-subtitle-landing mb-0">End-of-semester results by year and semester. GPA = Σ(P×N) ÷ ΣN (scale 0–4).</p>
</div>

@include('results.partials.grading-scale-reference')

<div class="module-result-remarks-legend">
    <span><span class="module-result-swatch pass"></span> Pass</span>
    <span><span class="module-result-swatch failed"></span> Failed</span>
    <span><span class="module-result-swatch incomplete"></span> Incomplete</span>
</div>

<div class="alert alert-info d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-clock-history flex-shrink-0 mt-1"></i>
    <div class="small mb-0">Click on a listed academic year and semester to view your end-of-semester module results.</div>
</div>

@if($yearSections->isEmpty())
<div class="alert alert-secondary">No end-of-semester results are published yet for your account.</div>
@else
<div id="module-results-accordion" class="module-results-accordion">
    @foreach($yearSections as $yearIndex => $year)
    @php
        $yearCollapseId = 'year-results-'.$year['academic_year'];
        $yearOpen = $yearIndex === 0;
    @endphp
    <div class="card card-landing mb-3 courses-tree-card">
        <div
            class="card-header-landing d-flex justify-content-between align-items-center gap-3 py-3 courses-fold-trigger {{ $yearOpen ? '' : 'collapsed' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $yearCollapseId }}"
            aria-expanded="{{ $yearOpen ? 'true' : 'false' }}"
            role="button"
            tabindex="0"
        >
            <div>
                <h2 class="h5 mb-0 fw-semibold">{{ $year['title'] }}</h2>
                <div class="year-section-subtitle">{{ $year['academic_year_label'] }}</div>
            </div>
            <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0 fs-5"></i>
        </div>
        <div id="{{ $yearCollapseId }}" class="collapse {{ $yearOpen ? 'show' : '' }} border-top border-light-subtle">
            <div class="card-body">
                @php $ys = $year['summary']; @endphp
                <div class="year-result-summary">
                    <h3>Year result summary</h3>
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="year-summary-stat">
                                <div class="label">Total credits</div>
                                <div class="value">{{ $ys['total_credits'] !== null ? number_format($ys['total_credits'], 1) : '—' }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="year-summary-stat">
                                <div class="label">Total grade points</div>
                                <div class="value">{{ $ys['total_grade_points'] !== null ? number_format($ys['total_grade_points'], 2) : '—' }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="year-summary-stat">
                                <div class="label">GPA</div>
                                <div class="value">{{ $ys['gpa'] !== null ? number_format($ys['gpa'], 4) : '—' }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="year-summary-stat">
                                <div class="label">Cumulative GPA / Award</div>
                                <div class="value" style="font-size:.95rem">{{ $ys['award_class'] ?? $ys['remarks'] ?? '—' }}</div>
                                @if($ys['gpa'] !== null)<div class="small text-muted">GPA {{ number_format($ys['gpa'], 2) }}</div>@endif
                            </div>
                        </div>
                    </div>
                </div>

                @foreach($year['semesters'] as $semIndex => $semester)
                @php
                    $semCollapseId = 'sem-results-'.$year['academic_year'].'-'.$semester['semester']->id;
                    $semOpen = $yearOpen && $semIndex === 0;
                    $semSummary = $semester['summary'];
                @endphp
                <div class="semester-nested-card">
                    <div
                        class="d-flex justify-content-between align-items-center gap-2 px-3 py-2 courses-fold-trigger {{ $semOpen ? '' : 'collapsed' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $semCollapseId }}"
                        aria-expanded="{{ $semOpen ? 'true' : 'false' }}"
                        role="button"
                        tabindex="0"
                    >
                        <div>
                            <div class="fw-semibold">{{ $semester['title'] }}</div>
                            <div class="small text-muted">{{ $semester['academic_year_label'] }}</div>
                        </div>
                        <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0"></i>
                    </div>
                    <div id="{{ $semCollapseId }}" class="collapse {{ $semOpen ? 'show' : '' }}">
                        @php
                            $semGpa = $semester['computed']['gpa'] ?? ($semSummary?->gpa !== null ? (float) $semSummary->gpa : null);
                            $semAward = $semester['computed']['award_class'] ?? $semSummary?->academic_remarks;
                        @endphp
                        @if($semGpa !== null || $semAward)
                        <div class="semester-sem-summary">
                            <span class="text-muted">Semester GPA:</span>
                            <strong>{{ $semGpa !== null ? number_format($semGpa, 2) : '—' }}</strong>
                            @if($semAward)
                            <span class="mx-2 text-muted">|</span>
                            <span class="text-muted">Classification:</span>
                            <strong>{{ $semAward }}</strong>
                            @endif
                        </div>
                        @endif
                        @include('results.partials.module-result-semester-table', [
                            'results' => $semester['results'],
                            'semesterId' => $semester['semester']->id,
                        ])
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@foreach($yearSections as $year)
    @foreach($year['semesters'] as $semester)
        @foreach($semester['results'] as $result)
            @include('results.partials.module-result-preview-modal', [
                'result' => $result,
                'semesterId' => $semester['semester']->id,
            ])
        @endforeach
    @endforeach
@endforeach
@endsection
