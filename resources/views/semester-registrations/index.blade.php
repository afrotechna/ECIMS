@extends('layouts.app')
@section('title', 'Semester Registrations')
@section('content')
@php
    $canManageRegistrations = auth()->user()->canModule('registrations', 'update');
    $pendingOnPage = $canManageRegistrations
        ? $registrations->getCollection()->filter(fn ($r) => $r->student && $r->status === 'pending' && $r->wizard_step === null)
        : collect();
@endphp

<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Student registrations</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-calendar-check me-2 opacity-90"></i>Student registrations</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('registration-wizard.start') }}" class="btn btn-primary btn-sm"><i class="bi bi-ui-checks-grid me-1"></i> Start registration (steps)</a>
        <a href="{{ route('semester-registrations.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Quick submit only</a>
    </div>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Academic year</label>
                <select name="academic_year" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($academicYearOptions as $start => $label)
                    <option value="{{ $start }}" {{ (string) request('academic_year') === (string) $start ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Semester</label>
                <select name="semester_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($semesters as $s)
                    <option value="{{ $s->id }}" {{ request('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['academic_year', 'semester_id', 'status']))
                    <a href="{{ route('semester-registrations.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($canManageRegistrations && $pendingOnPage->isNotEmpty())
<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form id="bulkApproveForm" method="POST" action="{{ route('semester-registrations.bulk-approve') }}" class="d-inline">
            @csrf
            <div id="bulkApproveIds"></div>
            <button type="submit" class="btn btn-sm btn-success" id="bulkApproveBtn">Approve selected</button>
        </form>
        <form id="bulkRejectForm" method="POST" action="{{ route('semester-registrations.bulk-reject') }}" class="d-inline ms-2">
            @csrf
            <div id="bulkRejectIds"></div>
            <input type="text" class="form-control form-control-sm d-inline-block w-auto ms-1" name="bulk_reject_notes" placeholder="Reason (optional)">
            <button type="submit" class="btn btn-sm btn-outline-danger">Reject selected</button>
        </form>
        <script>
            document.getElementById('selectAllPending')?.addEventListener('change', function() {
                document.querySelectorAll('.pending-cb').forEach(function(cb) { cb.checked = this.checked; }, this);
            });
            function getPendingIds() {
                return Array.from(document.querySelectorAll('.pending-cb:checked')).map(function(cb) { return cb.value; });
            }
            document.getElementById('bulkApproveForm')?.addEventListener('submit', function(e) {
                var ids = getPendingIds();
                if (ids.length === 0) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Nothing selected', text: 'Select at least one registration.' }); return; }
                var div = document.getElementById('bulkApproveIds');
                div.innerHTML = '';
                ids.forEach(function(id) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; div.appendChild(i); });
            });
            document.getElementById('bulkRejectForm')?.addEventListener('submit', function(e) {
                var ids = getPendingIds();
                if (ids.length === 0) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Nothing selected', text: 'Select at least one registration.' }); return; }
                var div = document.getElementById('bulkRejectIds');
                div.innerHTML = '';
                ids.forEach(function(id) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; div.appendChild(i); });
            });
        </script>
    </div>
</div>
@endif

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Registrations</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    @if($canManageRegistrations)
                    <th>@if($pendingOnPage->isNotEmpty())<input type="checkbox" id="selectAllPending" aria-label="Select all pending">@endif</th>
                    @endif
                    <th>Student</th><th>Semester</th><th>Status</th><th>Registered</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $r)
                <tr>
                    @if($canManageRegistrations)
                    <td>@if($r->status === 'pending' && $r->wizard_step === null)<input type="checkbox" class="form-check-input pending-cb" name="ids[]" value="{{ $r->id }}">@else<span class="text-muted">—</span>@endif</td>
                    @endif
                    <td>{{ $r->student?->full_name ?? '—' }}</td>
                    <td>{{ $r->semester?->label ?? '—' }}</td>
                    <td>
                        @if($r->status === 'approved')<span class="badge bg-success">Approved</span>
                        @elseif($r->status === 'rejected')<span class="badge bg-danger">Rejected</span>
                        @elseif($r->wizard_step)<span class="badge bg-info text-dark">Wizard step {{ $r->wizard_step }}</span>
                        @else<span class="badge bg-warning text-dark">Pending</span>@endif
                    </td>
                    <td>{{ $r->registered_at ? $r->registered_at->format('d/m/Y') : '-' }}</td>
                    <td class="text-end">
                        @if($r->wizard_step)
                            <a href="{{ route('registration-wizard.step', [$r, $r->wizard_step]) }}" class="btn btn-sm btn-primary">Continue steps</a>
                        @elseif($r->status === 'pending' && $canManageRegistrations)
                            <form action="{{ route('semester-registrations.approve', $r) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-success me-1">Approve</button></form>
                            <form action="{{ route('semester-registrations.reject', $r) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">Reject</button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ $canManageRegistrations ? 6 : 5 }}" class="text-center text-muted py-5">No registrations yet. <a href="{{ route('registration-wizard.start') }}">Start registration wizard</a> or <a href="{{ route('semester-registrations.create') }}">quick submit</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($registrations->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $registrations->links() }}</div>
    @endif
</div>
@endsection
