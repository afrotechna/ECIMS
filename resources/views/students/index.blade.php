@extends('layouts.app')

@section('title', 'Students')

@section('content')
<nav class="student-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <span aria-current="page">Students</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-people-fill me-2 opacity-90"></i>Students</h1>
        <p class="page-subtitle-landing mb-0">Register and manage students (system reg. no., NACTVET / NACTE registration, programme, intake).</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('students.import-admitted') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-person-lines-fill me-1"></i> Admitted intake</a>
        <a href="{{ route('students.import') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-upload me-1"></i> Bulk import</a>
        <a href="{{ route('students.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-person-plus me-1"></i> Register student</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm mb-3" role="alert">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm mb-3" role="alert">{{ session('error') }}</div>
@endif

@php
    $studentFilterParams = fn (?int $level = null, ?int $programmeId = null) => array_filter([
        'search' => request('search'),
        'programme_id' => $programmeId ?? request('programme_id'),
        'intake_year' => request('intake_year'),
        'status' => request('status'),
        'nta_level' => $level ?? request('nta_level'),
    ], fn ($v) => $v !== null && $v !== '');

    $ntaNavItems = [];
    foreach ([4, 5, 6] as $lvl) {
        $c = (int) ($ntaLevelCounts[$lvl] ?? 0);
        $ntaNavItems[] = [
            'href' => route('students.index', $studentFilterParams($lvl, $activeProgrammeId ?? null)),
            'active' => ($activeNtaLevel ?? null) === $lvl,
            'icon' => 'bi-'.$lvl.'-circle',
            'accent' => 'cohas-segment-nav__item--l'.$lvl,
            'title' => 'Level '.$lvl,
            'subtitle' => number_format($c).' '.($c === 1 ? 'student' : 'students'),
        ];
    }

    $progAccents = ['CMT' => 'cohas-segment-nav__item--cmt', 'MLT' => 'cohas-segment-nav__item--mlt'];
    $programmeNavItems = [[
        'href' => route('students.index', $studentFilterParams($activeNtaLevel, null)),
        'active' => empty($activeProgrammeId),
        'icon' => 'bi-grid-3x3-gap',
        'accent' => 'cohas-segment-nav__item--all',
        'title' => 'All programmes',
        'subtitle' => number_format($allStudentsCount ?? 0).' students',
    ]];
    foreach ($programmes as $p) {
        $c = (int) ($programmeFilterCounts[$p->id] ?? 0);
        $programmeNavItems[] = [
            'href' => route('students.index', $studentFilterParams($activeNtaLevel, (int) $p->id)),
            'active' => ($activeProgrammeId ?? null) === (int) $p->id,
            'icon' => $p->code === 'MLT' ? 'bi-droplet-half' : 'bi-heart-pulse',
            'accent' => $progAccents[$p->code] ?? 'cohas-segment-nav__item--all',
            'title' => $p->code,
            'subtitle' => number_format($c).' students',
        ];
    }
@endphp

@include('partials.segment-nav', [
    'ariaLabel' => 'NTA level',
    'eyebrow' => 'NTA level',
    'hint' => $activeNtaLevel ? null : 'Select a cohort',
    'clearUrl' => $activeNtaLevel ? route('students.index', $studentFilterParams(null, $activeProgrammeId)) : null,
    'clearLabel' => 'All levels',
    'clearIcon' => 'bi-grid-3x3-gap',
    'columns' => 3,
    'items' => $ntaNavItems,
])

@include('partials.segment-nav', [
    'ariaLabel' => 'Programme',
    'eyebrow' => 'Programme',
    'hint' => $activeProgrammeId ? null : 'CMT or MLT',
    'clearUrl' => $activeProgrammeId ? route('students.index', $studentFilterParams($activeNtaLevel, null)) : null,
    'clearLabel' => 'All programmes',
    'clearIcon' => 'bi-grid-3x3-gap',
    'columns' => min(4, count($programmeNavItems)),
    'items' => $programmeNavItems,
])

<div class="cohas-filter-panel">
    <div class="cohas-filter-panel__title"><i class="bi bi-funnel me-1"></i> Refine list</div>
    <form action="{{ route('students.index') }}" method="GET" class="row g-3 align-items-end">
        @if($activeNtaLevel ?? null)
            <input type="hidden" name="nta_level" value="{{ $activeNtaLevel }}">
        @endif
        @if($activeProgrammeId ?? null)
            <input type="hidden" name="programme_id" value="{{ $activeProgrammeId }}">
        @endif
        <div class="col-12 col-md-5 col-lg-4">
            <label class="form-label small mb-1 text-muted" for="filter-search">Search</label>
            <input type="text" class="form-control" id="filter-search" name="search" value="{{ request('search') }}" placeholder="Reg no, registration no, name…" autocomplete="off">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small mb-1 text-muted" for="filter-intake">Intake year</label>
            <select name="intake_year" id="filter-intake" class="form-select">
                <option value="">All</option>
                @foreach($intakeYears ?? [] as $y)
                    <option value="{{ $y }}" {{ request('intake_year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small mb-1 text-muted" for="filter-status">Status</label>
            <select name="status" id="filter-status" class="form-select">
                <option value="">All</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Apply</button>
            <a href="{{ route('students.index', array_filter(['nta_level' => $activeNtaLevel, 'programme_id' => $activeProgrammeId])) }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
        <span>
            <i class="bi bi-table me-2"></i>Student register
            @if($activeNtaLevel ?? null)
                <span class="text-muted fw-normal">· Level {{ $activeNtaLevel }}</span>
            @endif
            @if($activeProgrammeId ?? null)
                @php $activeProg = $programmes->firstWhere('id', $activeProgrammeId); @endphp
                @if($activeProg)
                    <span class="text-muted fw-normal">· {{ $activeProg->code }}</span>
                @endif
            @endif
        </span>
        @if($students->total() > 0)
            <span class="badge bg-light text-dark fw-normal border">{{ number_format($students->total()) }} {{ ($activeNtaLevel ?? null) ? 'in this level' : 'total' }}</span>
        @endif
    </div>
    <div class="students-toolbar">
        <span>
            @if($students->total() === 0)
                No records match your filters.
            @else
                Showing <strong>{{ $students->firstItem() }}</strong>–<strong>{{ $students->lastItem() }}</strong> of <strong>{{ $students->total() }}</strong>
            @endif
        </span>
        @if(request()->hasAny(['search', 'intake_year', 'status']) || (request('programme_id') && ! $activeProgrammeId))
            <span class="text-muted small">More filters active — <a href="{{ route('students.index', array_filter(['nta_level' => $activeNtaLevel, 'programme_id' => $activeProgrammeId])) }}" class="text-decoration-none">clear search &amp; extras</a></span>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive table-responsive-students-landing">
            <table class="table table-hover align-middle mb-0 table-students-landing">
                <thead>
                    <tr>
                        <th scope="col">Reg no.</th>
                        <th scope="col">Registration</th>
                        <th scope="col">Name</th>
                        <th scope="col">Programme</th>
                        <th scope="col" class="text-center">Intake</th>
                        <th scope="col" class="text-center">NTA</th>
                        <th scope="col" class="text-center">Status</th>
                        <th scope="col" class="text-center">Portal</th>
                        <th scope="col" class="text-end actions-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    <tr>
                        <td class="reg-cell text-nowrap">{{ $s->reg_no }}</td>
                        <td class="nacte-cell"><code>{{ $s->nactvet_reg_no }}</code></td>
                        <td class="name-cell">
                            <span class="name-text" title="{{ $s->full_name }}">{{ $s->full_name }}</span>
                        </td>
                        <td>
                            <span class="fw-semibold text-nowrap">{{ $s->programme->code ?? '—' }}</span>
                            @if($s->programme)
                                <span class="d-none d-xl-inline text-muted small"> · {{ \Illuminate\Support\Str::limit($s->programme->name, 28) }}</span>
                            @endif
                        </td>
                        <td class="text-center text-nowrap">{{ $s->intake_year }}</td>
                        <td class="text-center">
                            @if($s->nta_level)
                                <span class="badge bg-secondary">{{ $s->nta_level }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $s->status === 'active' ? 'success' : 'secondary' }}">{{ $s->status }}</span>
                        </td>
                        <td class="text-center">
                            @if($s->email && in_array($s->email, $emailsWithUser ?? []))
                                <span class="badge bg-primary">Yes</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-end actions-cell">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Actions for {{ $s->full_name }}">
                                <a href="{{ route('students.show', $s) }}" class="btn btn-outline-primary" title="View profile"><i class="bi bi-eye"></i></a>
                                @if(auth()->user()->canAccessFinance())
                                <a href="{{ route('students.ledger', $s) }}" class="btn btn-outline-info" title="Fee ledger"><i class="bi bi-wallet2"></i></a>
                                @endif
                                <a href="{{ route('students.edit', $s) }}" class="btn btn-cohas-edit" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <p class="mb-2">No students found.</p>
                            <a href="{{ route('students.create') }}" class="btn btn-sm btn-primary">Register a student</a>
                            <span class="mx-1 text-muted">or</span>
                            <a href="{{ route('students.import') }}" class="btn btn-sm btn-outline-primary">Bulk import</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-light border-0 py-3 d-flex justify-content-center">
        {{ $students->links() }}
    </div>
    @endif
</div>
@endsection
