@extends('layouts.app')
@section('title', 'Clinical procedures catalogue')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('clinical-rotations.index') }}">Clinical rotation</a>
    <span class="mx-2">/</span>
    <span>Procedures catalogue</span>
</nav>

<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-list-check me-2 opacity-90"></i>Clinical procedures catalogue</h1>
        <p class="page-subtitle-landing mb-0">Skills and procedures students log for NTA Clinical Medicine (CMT)</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('clinical-logbook.index') }}" class="btn btn-outline-primary btn-sm">Logbook review</a>
        <a href="{{ route('clinical.framework') }}" class="btn btn-outline-secondary btn-sm">Process framework</a>
        <a href="{{ route('clinical-procedures.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add procedure</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <label class="form-label small mb-0">NTA level</label>
        <select name="nta_level" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach([4,5,6] as $l)
                <option value="{{ $l }}" {{ (int)($nta ?? 0) === $l ? 'selected' : '' }}>NTA {{ $l }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="card card-landing">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>NTA</th><th>Code</th><th>Procedure</th><th>Department</th><th>Assessment</th><th>Min.</th><th>Active</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($procedures as $p)
                <tr>
                    <td>{{ $p->nta_level }}</td>
                    <td class="fw-semibold">{{ $p->code }}</td>
                    <td>{{ $p->name }}</td>
                    <td class="small">{{ $p->departmentLabel() }}</td>
                    <td class="small" style="max-width:14rem">{{ $p->assessment_modes ?: '—' }}</td>
                    <td>{{ $p->min_required_count ?? '—' }}</td>
                    <td>@if($p->is_active)<span class="badge bg-success">Yes</span>@else<span class="badge bg-secondary">No</span>@endif</td>
                    <td>@include('partials.action-edit', ['href' => route('clinical-procedures.edit', $p), 'iconOnly' => true])</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($procedures->hasPages())<div class="card-body">{{ $procedures->links() }}</div>@endif
</div>
@endsection
