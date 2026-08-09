@extends('layouts.app')
@section('title', 'Semesters')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Semesters</span>
</nav>

@if($programmes->isEmpty())
<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <span class="mb-0">Add at least one <strong>programme</strong> before defining semesters and modules.</span>
    @canModule('programmes', 'create')
    <a href="{{ route('programmes.create') }}" class="btn btn-sm btn-dark shrink-0"><i class="bi bi-plus-lg me-1"></i> Add programme</a>
    @endcanModule
</div>
@else
<div class="card card-landing mb-3">
    <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="small">
            <span class="text-muted me-2">Programmes</span>
            @foreach($programmes as $p)
                <span class="badge bg-light text-dark border me-1">{{ $p->code }}</span>
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('programmes.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-collection me-1"></i> Manage programmes</a>
            @canModule('programmes', 'create')
            <a href="{{ route('programmes.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i> Programme</a>
            @endcanModule
            @canModule('courses', 'create')
            <a href="{{ route('courses.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Module</a>
            @endcanModule
        </div>
    </div>
</div>
@endif

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-calendar3 me-2 opacity-90"></i>Semesters</h1>
        <p class="page-subtitle-landing mb-0">Manage academic semesters and key dates.</p>
    </div>
    @canModule('semesters', 'create')
    <a href="{{ route('semesters.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Semester</a>
    @endcanModule
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('semesters.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label for="filter_academic_year" class="form-label">Academic year</label>
                <select name="academic_year" id="filter_academic_year" class="form-select" onchange="this.form.submit()" data-no-search>
                    <option value="">All years</option>
                    @foreach($academicYearOptions as $start => $yearLabel)
                        <option value="{{ $start }}" {{ (string) request('academic_year') === (string) $start ? 'selected' : '' }}>{{ $yearLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Apply</button>
                @if(request()->filled('academic_year'))
                    <a href="{{ route('semesters.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

@php
    $bulkDelete = [
        'bulkModule' => 'semesters',
        'bulkAction' => route('semesters.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteSemesters',
        'bulkTableId' => 'semestersTable',
        'bulkItemCount' => $semesters->count(),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Semesters</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="semestersTable">
                @php
                    $showSemesterActions = auth()->user()->canModule('semesters', 'update') || auth()->user()->canModule('semesters', 'delete');
                @endphp
                <thead>
                    <tr>
                        @include('partials.bulk-delete.th', $bulkDelete)
                        <th>Academic year</th>
                        <th>Semester</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Registration</th>
                        @if($showSemesterActions)
                        <th class="text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($semesters as $s)
                    <tr>
                        @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $s->id]))
                        <td>{{ $s->academicYearRange() }}</td>
                        <td><strong>{{ $s->periodName() }}</strong></td>
                        <td>@if($s->start_date){{ $s->start_date->format('d/m/Y') }}@else—@endif</td>
                        <td>@if($s->end_date){{ $s->end_date->format('d/m/Y') }}@else—@endif</td>
                        <td>@if($s->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                        <td>
                            @php
                                $isOpen = $s->registration_status === \App\Models\Semester::REGISTRATION_OPEN;
                                $isComplete = $s->registration_status === \App\Models\Semester::REGISTRATION_COMPLETE;
                            @endphp
                            @if($isComplete)
                                <span class="badge bg-primary d-inline-flex align-items-center gap-1" title="Registration complete">
                                    <i class="bi bi-check2-all"></i> Complete
                                </span>
                            @elseif(auth()->user()->isAdmin())
                                @if($isOpen)
                                    <form action="{{ route('semesters.close-registration', $s) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill" title="Registration is open — click to close">Open</button>
                                    </form>
                                    <form action="{{ route('semesters.complete-registration', $s) }}" method="POST" class="d-inline ms-1">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill">Mark complete</button>
                                    </form>
                                @else
                                    @php $blockedReason = $s->canOpenRegistration(); @endphp
                                    @if($blockedReason)
                                        <span class="badge bg-secondary rounded-pill">Closed</span>
                                        <span class="d-block small text-muted mt-1">{{ $blockedReason }}</span>
                                    @else
                                        <form action="{{ route('semesters.open-registration', $s) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" title="Registration is closed — click to open">Closed</button>
                                        </form>
                                    @endif
                                @endif
                            @else
                                <span class="badge {{ $isOpen ? 'bg-success' : 'bg-secondary' }}">{{ $s->registrationStatusLabel() }}</span>
                            @endif
                        </td>
                        @if($showSemesterActions)
                        <td class="text-end">
                            @canModule('semesters', 'update')
                            @include('partials.action-edit', ['href' => route('semesters.edit', $s), 'class' => 'me-1', 'iconOnly' => true])
                            @endcanModule
                            @canModule('semesters', 'delete')
                            <form action="{{ route('semesters.destroy', $s) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete semester?'])
                            </form>
                            @endcanModule
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $showSemesterActions ? 8 : 7 }}" class="text-center text-muted py-5">
                        No semesters yet.
                        @canModule('semesters', 'create')
                        <a href="{{ route('semesters.create') }}">Add one</a>
                        @endcanModule
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($semesters->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $semesters->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
