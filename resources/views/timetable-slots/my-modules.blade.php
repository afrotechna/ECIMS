@extends('layouts.app')
@section('title', 'My Modules Detail')
@section('content')
@php
    $catalog = $catalog ?? [];
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>My Modules Detail</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-journal-bookmark me-2 opacity-90"></i>My Modules Detail</h1>
        <p class="page-subtitle-landing mb-0">
            {{ $catalog['programme_code'] ?? '' }} — {{ $catalog['programme_name'] ?? '' }}
            · {{ $catalog['nta_level_label'] ?? '' }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('my.module-registration') }}" class="btn btn-primary btn-sm"><i class="bi bi-check2-square me-1"></i> Register modules for semester</a>
        <span class="badge bg-light text-dark fs-6">Academic year {{ $catalog['academic_year_label'] ?? '' }}</span>
    </div>
</div>

<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-funnel me-2"></i>Academic year</div>
    <div class="card-body py-3">
        <form method="GET" action="{{ route('my.modules') }}" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="academic_year" class="form-label">Session</label>
                <select name="academic_year" id="academic_year" class="form-select" data-no-search>
                    @foreach($academicYearOptions ?? [] as $year => $label)
                    <option value="{{ $year }}" {{ (int) ($academicYearStart ?? 0) === (int) $year ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Show modules</button>
            </div>
        </form>
    </div>
</div>

<div id="student-modules-collapse-scope">
    @foreach([
        ['key' => 'one', 'label' => 'Semester one', 'modules' => $catalog['semester_one'] ?? collect(), 'credits' => $catalog['semester_one_credits'] ?? 0, 'open' => true],
        ['key' => 'two', 'label' => 'Semester two', 'modules' => $catalog['semester_two'] ?? collect(), 'credits' => $catalog['semester_two_credits'] ?? 0, 'open' => false],
    ] as $block)
    <div class="card card-landing mb-3 courses-tree-card">
        <div
            class="card-header-landing d-flex justify-content-between align-items-center gap-2 py-3 courses-fold-trigger {{ ($block['open'] ?? false) ? '' : 'collapsed' }}"
            data-bs-toggle="collapse"
            data-bs-target="#student-sem-modules-{{ $block['key'] }}"
            aria-expanded="{{ ($block['open'] ?? false) ? 'true' : 'false' }}"
            role="button"
            tabindex="0"
        >
            <h2 class="h5 mb-0 fw-semibold text-uppercase">{{ $block['label'] }}</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark">{{ $block['modules']->count() }} module(s)</span>
                <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0"></i>
            </div>
        </div>
        <div id="student-sem-modules-{{ $block['key'] }}" class="collapse {{ ($block['open'] ?? false) ? 'show' : '' }} border-top border-light-subtle">
            <div class="card-body pb-3 pt-3">
                @include('timetable-slots.partials.module-semester-table', [
                    'modules' => $block['modules'],
                    'creditsTotal' => $block['credits'],
                ])
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection

@push('styles')
<style>
.courses-fold-trigger { cursor: pointer; user-select: none; }
.courses-fold-icon { transition: transform 0.2s ease; display: inline-block; }
.courses-fold-trigger.collapsed .courses-fold-icon { transform: rotate(-90deg); }
</style>
@endpush
