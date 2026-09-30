@extends('layouts.app')

@section('title', 'Manage Courses')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Module catalogue</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-journal-book-fill me-2 opacity-90"></i>Module catalogue</h1>
        @if($catalogueFilterActive)
            <p class="page-subtitle-landing mb-0">Modules linked to the selected teaching semester @if($currentSemester)<strong>{{ $currentSemester->label }}</strong>@endif. <a href="{{ route('courses.index') }}" class="text-white">Show full catalogue</a> (programme · NTA level · semester tree).</p>
        @else
            <p class="page-subtitle-landing mb-0">By programme, NTA level, and semester.</p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($catalogueFilterActive)
            <a href="{{ route('courses.index') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-diagram-3 me-1"></i>Full catalogue</a>
        @endif
        @canModule('programmes', 'create')
        <a href="{{ route('programmes.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-collection me-1"></i> Programme</a>
        @endcanModule
        @php
            $canDeleteCourses = auth()->user()?->canModule('courses', 'delete') ?? false;
            $courseCountOnPage = 0;
            if ($catalogueFilterActive) {
                $courseCountOnPage = $filteredCourses->count();
            } elseif (! empty($programmesTree)) {
                foreach ($programmesTree as $block) {
                    foreach ($block['levels'] ?? [] as $semesters) {
                        foreach ($semesters as $semCourses) {
                            $courseCountOnPage += $semCourses->count();
                        }
                    }
                }
            }
            $bulkDelete = [
                'bulkModule' => 'courses',
                'bulkAction' => route('courses.bulk-destroy'),
                'bulkFormId' => 'bulkDeleteCourses',
                'bulkScopeId' => 'coursesBulkScope',
                'bulkItemCount' => $courseCountOnPage,
                'bulkHidden' => array_filter([
                    'semester_id' => $semesterId ?? null,
                    'programme_id' => $programmeId ?? null,
                ]),
                'bulkConfirm' => 'Delete :count selected module(s)? This cannot be undone.',
                'bulkButtonLabel' => 'Delete selected modules',
            ];
        @endphp
        @if($canDeleteCourses && $courseCountOnPage > 0)
            @include('partials.bulk-delete.toolbar', $bulkDelete)
        @endif
        @php
            // Checkboxes to select modules only render when the user has courses:delete
            // (see partials.bulk-delete.td), so tie this to the same gate as well as
            // courses:update — that's the combination Administrator (the only role
            // currently granted courses:update) actually has.
            $canAssignSemester = $canDeleteCourses && (auth()->user()?->canModule('courses', 'update') ?? false);
        @endphp
        @if($canAssignSemester && $courseCountOnPage > 0 && isset($semestersForFilter) && $semestersForFilter->isNotEmpty())
        <form
            id="bulkAssignSemesterForm"
            method="POST"
            action="{{ route('courses.bulk-assign-semester') }}"
            class="d-flex align-items-center gap-1 bulk-delete-form no-print"
            data-bulk-scope="coursesBulkScope"
            data-bulk-confirm="Assign :count selected module(s) to the chosen semester?"
            data-bulk-confirm-button="Assign"
            data-bulk-confirm-color="#0f766e"
            data-bulk-confirm-icon="question"
            data-bulk-group-by="nta-level"
        >
            @csrf
            <div class="bulk-delete-ids"></div>
            <input type="hidden" name="return_semester_id" value="{{ $semesterId ?? '' }}">
            <input type="hidden" name="return_programme_id" value="{{ $programmeId ?? '' }}">
            <select name="assign_semester_id" class="form-select form-select-sm" style="width:auto" required>
                <option value="">Assign selected to…</option>
                @foreach($semestersForFilter as $s)
                    <option value="{{ $s->id }}">{{ $s->label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary bulk-delete-submit d-none" title="Assign selected modules to the chosen semester">
                <i class="bi bi-calendar2-check me-1"></i>Assign <span class="badge bg-primary ms-1 bulk-delete-count">0</span>
            </button>
        </form>
        @endif
        @canModule('courses', 'create')
        <a href="{{ route('courses.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add module</a>
        @endcanModule
    </div>
</div>

<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-funnel me-2"></i>Filter by semester</div>
    <div class="card-body py-3">
        <form action="{{ route('courses.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="filter_semester_id" class="form-label">Teaching semester</label>
                <select name="semester_id" id="filter_semester_id" class="form-select">
                    <option value="">— Full catalogue (tree) —</option>
                    @foreach($semestersForFilter as $s)
                        <option value="{{ $s->id }}" {{ (string) ($semesterId ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="filter_programme_id" class="form-label">Programme (optional)</label>
                <select name="programme_id" id="filter_programme_id" class="form-select">
                    <option value="">All programmes</option>
                    @foreach($programmesForFilter as $p)
                        <option value="{{ $p->id }}" {{ (string) ($programmeId ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Apply filter</button>
                <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if($catalogueFilterActive)
    <div class="card card-landing">
        <div class="card-header-landing d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span><i class="bi bi-list-ul me-2"></i>Modules for this semester</span>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if($currentSemester)
                    <span class="badge bg-light text-dark">{{ $currentSemester->label }}</span>
                @endif
                @if($canDeleteCourses && $courseCountOnPage > 0)
                    <input type="checkbox" class="form-check-input bulk-delete-select-all" data-bulk-scope="coursesBulkScope" aria-label="Select all modules on this page">
                @endif
            </div>
        </div>
        <div class="card-body" id="coursesBulkScope" data-bulk-delete-scope>
            @if($filteredCourses->isEmpty())
                <p class="text-muted mb-0">
                    No modules linked to this semester yet (with the programme filter if used).
                    @canModule('courses', 'create')
                    <a href="{{ route('courses.create') }}">Add a module</a> and assign it to this semester.
                    @endcanModule
                </p>
            @else
                @php
                    $sortLink = function (string $column, string $label) use ($sort, $direction) {
                        $nextDirection = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
                        $icon = $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted';
                        $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection]);

                        return '<a href="'.$url.'" class="text-decoration-none text-reset d-inline-flex align-items-center gap-1">'.$label.' <i class="bi '.$icon.' small"></i></a>';
                    };
                @endphp
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                @include('partials.bulk-delete.th', $bulkDelete)
                                <th>#</th>
                                <th>{!! $sortLink('code', 'Module code') !!}</th>
                                <th>{!! $sortLink('name', 'Course / Module') !!}</th>
                                <th>Programme</th>
                                <th>{!! $sortLink('year_of_study', 'Year') !!}</th>
                                <th>{!! $sortLink('ca_weight', 'CA %') !!}</th>
                                <th>{!! $sortLink('exam_weight', 'SE %') !!}</th>
                                <th>{!! $sortLink('credits', 'Credits') !!}</th>
                                @canModule('courses', 'update')
                                <th class="text-end">Actions</th>
                                @endcanModule
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filteredCourses as $i => $c)
                            <tr>
                                @include('partials.bulk-delete.td', array_merge($bulkDelete, [
                                    'bulkRowId' => $c->id,
                                    'bulkNtaLevel' => $c->resolvedNtaLevel(),
                                    'bulkGroupLabel' => ($c->programme->code ?? '?').' L'.$c->resolvedNtaLevel(),
                                ]))
                                <td>{{ $filteredCourses->firstItem() + $i }}</td>
                                <td><strong>{{ $c->code }}</strong></td>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->programme->code ?? '—' }}</td>
                                <td>{{ $c->year_of_study }}</td>
                                <td>{{ $c->ca_weight }}</td>
                                <td>{{ $c->exam_weight }}</td>
                                <td>{{ $c->credits }}</td>
                                @canModule('courses', 'update')
                                <td class="text-end">
                                    @include('partials.action-edit', ['href' => route('courses.edit', array_filter([
                                        'course' => $c,
                                        'return_semester_id' => $semesterId,
                                        'return_programme_id' => $programmeId ?: $c->programme_id,
                                    ])), 'iconOnly' => true])
                                </td>
                                @endcanModule
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                @if($canDeleteCourses && $courseCountOnPage > 0)
                                <td class="border-top"></td>
                                @endif
                                <td colspan="7" class="fw-semibold border-top text-start">Total credits (all matching modules)</td>
                                <td class="fw-semibold border-top">{{ number_format($filteredCreditsTotal, 2, '.', '') }}</td>
                                @canModule('courses', 'update')
                                <td class="border-top"></td>
                                @endcanModule
                            </tr>
                        </tfoot>
                    </table>
                @if($filteredCourses->hasPages())
                <div class="card-footer bg-transparent">
                    {{ $filteredCourses->appends(request()->query())->links() }}
                </div>
                @endif
            @endif
        </div>
    </div>
@elseif(empty($programmesTree))
<div class="card card-landing">
    <div class="card-body text-center text-muted py-5">
        @canModule('programmes', 'create')
        <p class="mb-2">Add a <a href="{{ route('programmes.create') }}">programme</a> first, then semesters, then modules.</p>
        <a href="{{ route('programmes.create') }}" class="btn btn-primary btn-sm me-1">Add programme</a>
        @else
        <p class="mb-0">No programmes set up yet.</p>
        @endcanModule
        @canModule('courses', 'create')
        <a href="{{ route('courses.create') }}" class="btn btn-outline-primary btn-sm">Add module</a>
        @endcanModule
    </div>
</div>
@else
<div id="coursesBulkScope" data-bulk-delete-scope>
<div id="courses-collapse-scope">
@php
    $levelOrder = [4, 5, 6];
    $semesterOrder = [1, 2, 0];
@endphp
@foreach($programmesTree as $pIndex => $block)
@php
    $programme = $block['programme'];
    $pid = $programme->id;
    $openFirstLevel = false;
    $levelOpened = false;
@endphp
<div class="card card-landing mb-3 courses-tree-card">
    <div
        class="card-header-landing d-flex justify-content-between align-items-center gap-2 py-3 courses-fold-trigger collapsed"
        data-bs-toggle="collapse"
        data-bs-target="#prog-body-{{ $pid }}"
        aria-expanded="false"
        role="button"
        tabindex="0"
    >
        <h2 class="h5 mb-0 fw-semibold"><i class="bi bi-mortarboard me-2 text-primary"></i>{{ $programme->name }} <span class="text-muted fw-normal">({{ $programme->code }})</span></h2>
        <div class="d-flex align-items-center gap-2">
            @canModule('courses', 'create')
            <a href="{{ route('courses.create', ['programme_id' => $pid]) }}" class="btn btn-sm btn-light text-primary" title="Add module for this programme" onclick="event.stopPropagation();">+ Module</a>
            @endcanModule
            <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0"></i>
        </div>
    </div>
    <div id="prog-body-{{ $pid }}" class="collapse border-top border-light-subtle">
        <div class="card-body pb-3 pt-3">
            @foreach($levelOrder as $level)
                @continue(! isset($block['levels'][$level]))
                @php
                    $semesters = $block['levels'][$level];
                    $lvlUncollapsed = $openFirstLevel && ! $levelOpened;
                    if ($lvlUncollapsed) { $levelOpened = true; }
                    $semOpened = false;
                @endphp
                <div class="mb-3 ms-md-2 border-start border-2 border-primary-subtle ps-3">
                    <div
                        class="d-flex justify-content-between align-items-center gap-2 py-2 courses-fold-trigger {{ $lvlUncollapsed ? '' : 'collapsed' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#lvl-{{ $pid }}-{{ $level }}"
                        aria-expanded="{{ $lvlUncollapsed ? 'true' : 'false' }}"
                        role="button"
                        tabindex="0"
                    >
                        <h3 class="h6 mb-0 fw-semibold text-primary">NTA Level {{ $level }}</h3>
                        <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0 small"></i>
                    </div>
                    <div id="lvl-{{ $pid }}-{{ $level }}" class="collapse {{ $lvlUncollapsed ? 'show' : '' }} border-start border-2 border-secondary-subtle ms-2 ps-3 mt-1">
                        @foreach($semesterOrder as $semNum)
                            @continue(! isset($semesters[$semNum]) || $semesters[$semNum]->isEmpty())
                            @php
                                $semCourses = $semesters[$semNum];
                                $semesterCreditsTotal = $semCourses->sum(fn ($course) => (float) $course->credits);
                                $semUncollapsed = $lvlUncollapsed && ! $semOpened;
                                if ($semUncollapsed) { $semOpened = true; }
                            @endphp
                            <div class="mb-3">
                                <div
                                    class="d-flex justify-content-between align-items-center gap-2 py-2 small fw-semibold text-uppercase text-muted courses-fold-trigger {{ $semUncollapsed ? '' : 'collapsed' }}"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#sem-{{ $pid }}-{{ $level }}-{{ $semNum }}"
                                    aria-expanded="{{ $semUncollapsed ? 'true' : 'false' }}"
                                    role="button"
                                    tabindex="0"
                                >
                                    <span>
                                        @if($semNum === 1)
                                        Semester one
                                        @elseif($semNum === 2)
                                        Semester two
                                        @else
                                        Semester not assigned
                                        @endif
                                    </span>
                                    <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0"></i>
                                </div>
                                <div id="sem-{{ $pid }}-{{ $level }}-{{ $semNum }}" class="collapse {{ $semUncollapsed ? 'show' : '' }}">
                                    <div class="rounded border">
                                        <table class="table table-hover align-middle mb-0 table-sm courses-modules-table">
                                            <colgroup>
                                                @if($canDeleteCourses && $courseCountOnPage > 0)
                                                <col style="width: 5%">
                                                @endif
                                                <col style="width: 15%">
                                                <col style="width: 38%">
                                                <col style="width: 9%">
                                                <col style="width: 9%">
                                                <col style="width: 10%">
                                                @canModule('courses', 'update')
                                                <col style="width: 10%">
                                                @endcanModule
                                            </colgroup>
                                            <thead class="table-light">
                                                <tr>
                                                    @include('partials.bulk-delete.th', $bulkDelete)
                                                    <th>Module code</th>
                                                    <th>Course / Module</th>
                                                    <th>CA %</th>
                                                    <th>SE %</th>
                                                    <th>Credits</th>
                                                    @canModule('courses', 'update')
                                                    <th class="text-end">Actions</th>
                                                    @endcanModule
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($semCourses as $c)
                                                @php
                                                    $editReturnSemesterId = null;
                                                    if ($semNum === 1 || $semNum === 2) {
                                                        $editReturnSemesterId = $c->semesters->where('number', $semNum)->sortByDesc('academic_year')->first()?->id;
                                                    }
                                                    if ($editReturnSemesterId === null) {
                                                        $editReturnSemesterId = $c->semesters->sortByDesc(fn ($s) => sprintf('%08d-%02d', $s->academic_year, $s->number))->first()?->id;
                                                    }
                                                @endphp
                                                <tr>
                                                    @include('partials.bulk-delete.td', array_merge($bulkDelete, [
                                                        'bulkRowId' => $c->id,
                                                        'bulkNtaLevel' => $level,
                                                        'bulkGroupLabel' => $programme->code.' L'.$level,
                                                    ]))
                                                    <td><strong>{{ $c->code }}</strong></td>
                                                    <td>{{ $c->name }}</td>
                                                    <td>{{ $c->ca_weight }}</td>
                                                    <td>{{ $c->exam_weight }}</td>
                                                    <td>{{ $c->credits }}</td>
                                                    @canModule('courses', 'update')
                                                    <td class="text-end">
                                                        @include('partials.action-edit', ['href' => route('courses.edit', array_filter([
                                                            'course' => $c,
                                                            'return_semester_id' => $editReturnSemesterId,
                                                            'return_programme_id' => $programme->id,
                                                        ])), 'iconOnly' => true])
                                                    </td>
                                                    @endcanModule
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    @if($canDeleteCourses && $courseCountOnPage > 0)
                                                    <td class="border-top"></td>
                                                    @endif
                                                    <td colspan="4" class="fw-semibold border-top text-start">Total credits</td>
                                                    <td class="fw-semibold border-top">{{ number_format($semesterCreditsTotal, 2, '.', '') }}</td>
                                                    @canModule('courses', 'update')
                                                    <td class="border-top"></td>
                                                    @endcanModule
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach
</div>
</div>
@endif
@include('partials.bulk-delete.scripts')
@endsection

@push('styles')
<style>
.courses-fold-trigger { cursor: pointer; user-select: none; }
.courses-fold-icon { transition: transform 0.2s ease; display: inline-block; }
/* Panel hidden: chevron points sideways; panel open: points down */
.courses-fold-trigger.collapsed .courses-fold-icon { transform: rotate(-90deg); }

/* Fixed column widths (via <colgroup>) so the table always fits its card —
   long module names wrap onto a second line instead of stretching the
   table past the viewport and forcing a horizontal scrollbar. */
.courses-modules-table { table-layout: fixed; width: 100%; }
.courses-modules-table th,
.courses-modules-table td {
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
}
</style>
@endpush

