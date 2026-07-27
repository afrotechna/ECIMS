@extends('layouts.app')
@section('title', 'My Modules Assessments')
@push('styles')
<style>
    .ca-remarks-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem 2rem; margin-bottom: 1.25rem; font-size: .8125rem; color: #475569; }
    .ca-remarks-legend span { display: inline-flex; align-items: center; gap: .45rem; }
    .ca-remarks-swatch { width: 1.1rem; height: 1.1rem; border-radius: 2px; border: 1px solid rgba(15, 23, 42, .12); }
    .ca-remarks-swatch.pass { background: #bbf7d0; }
    .ca-remarks-swatch.failed { background: #fecdd3; }
    .ca-remarks-swatch.incomplete { background: #bfdbfe; }
    .ca-assessments-table thead th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; white-space: nowrap; }
    .ca-assessments-table td { vertical-align: middle; font-size: .875rem; }
    .ca-remark-cell { font-weight: 600; text-align: center; }
    .ca-remark-cell.pass { background: #bbf7d0; color: #14532d; }
    .ca-remark-cell.failed { background: #fecdd3; color: #9f1239; }
    .ca-remark-cell.incomplete { background: #bfdbfe; color: #1e3a8a; }
    .ca-section-year { font-size: .8125rem; color: #64748b; font-weight: 500; margin-top: .15rem; }
    .ca-assessment-accordion .courses-fold-trigger { cursor: pointer; user-select: none; }
    .ca-assessment-accordion .courses-fold-icon { transition: transform 0.2s ease; display: inline-block; }
    .ca-assessment-accordion .courses-fold-trigger.collapsed .courses-fold-icon { transform: rotate(-90deg); }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Home</a>
    <span class="mx-2">/</span>
    <span>My Modules Assessments</span>
</nav>

<div class="page-header-landing mb-3">
    <h1 class="page-title-landing"><i class="bi bi-journal-check me-2 opacity-90"></i>My Modules Assessments</h1>
    <p class="page-subtitle-landing mb-0">Continuous assessment (CA) marks and remarks by academic year and semester.</p>
</div>

<div class="ca-remarks-legend">
    <span><span class="ca-remarks-swatch pass"></span> Pass</span>
    <span><span class="ca-remarks-swatch failed"></span> Failed</span>
    <span><span class="ca-remarks-swatch incomplete"></span> Incomplete</span>
</div>

<div class="alert alert-info d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
    <div class="small mb-0">Click on a listed academic year and semester to view your module assessments.</div>
</div>

@if($sections->isEmpty())
<div class="alert alert-secondary">No continuous assessment records are published yet for your account.</div>
@else
<div id="ca-assessments-accordion" class="ca-assessment-accordion">
    @foreach($sections as $index => $section)
    @php
        $collapseId = 'ca-sem-'.$section['semester']->id;
        $isOpen = $index === 0;
    @endphp
    <div class="card card-landing mb-3 courses-tree-card">
        <div
            class="card-header-landing d-flex justify-content-between align-items-center gap-3 py-3 courses-fold-trigger {{ $isOpen ? '' : 'collapsed' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $collapseId }}"
            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
            role="button"
            tabindex="0"
        >
            <div>
                <h2 class="h5 mb-0 fw-semibold">{{ $section['title'] }}</h2>
                <div class="ca-section-year">{{ $section['academic_year_label'] }}</div>
            </div>
            <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0 fs-5"></i>
        </div>
        <div id="{{ $collapseId }}" class="collapse {{ $isOpen ? 'show' : '' }} border-top border-light-subtle">
            <div class="card-body p-0">
                @include('results.partials.ca-assessment-semester-table', [
                    'results' => $section['results'],
                    'semesterId' => $section['semester']->id,
                ])
            </div>
        </div>
    </div>
    @endforeach
</div>

<p class="small text-muted mt-2 mb-0">
    <strong>Pass</strong> — you may sit the end-of-semester examination for that module.
    <strong>Failed</strong> — not allowed to sit the end-of-semester examination.
    <strong>Incomplete</strong> — CA mark not yet published.
    Pass requires at least {{ (int) config('college.ca_pass_percent', 40) }}% of {{ (int) config('college.ca_max_mark', 40) }} CA marks.
</p>
@endif

@foreach($sections as $section)
    @foreach($section['results'] as $result)
        @include('results.partials.ca-assessment-preview-modal', ['result' => $result, 'semesterId' => $section['semester']->id])
    @endforeach
@endforeach
@endsection
