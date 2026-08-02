@extends('layouts.app')
@section('title', 'Results approvals')
@push('styles')
<style>
    .ca-remark-cell { font-weight: 600; text-align: center; }
    .ca-remark-cell.pass { background: #bbf7d0; color: #14532d; }
    .ca-remark-cell.failed { background: #fecdd3; color: #9f1239; }
    .ca-remark-cell.incomplete { background: #bfdbfe; color: #1e3a8a; }
    .ca-approval-grid thead th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; white-space: nowrap; }
    .ca-approval-grid td { vertical-align: middle; font-size: .8125rem; }
    .approval-summary-stat { text-align: center; }
    .approval-summary-stat .value { font-size: 1.5rem; font-weight: 800; display: block; line-height: 1.1; }
    .approval-summary-stat .label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .approval-summary-stat.pass .value { color: #14532d; }
    .approval-summary-stat.fail .value { color: #9f1239; }
    .approvals-fold-trigger { cursor: pointer; user-select: none; }
    .approvals-fold-icon { transition: transform 0.2s ease; display: inline-block; }
    .approvals-fold-trigger.collapsed .approvals-fold-icon { transform: rotate(-90deg); }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('results.index') }}">Results</a>
    <span class="mx-2">/</span>
    <span>Approvals</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing mb-0"><i class="bi bi-check2-square me-2 opacity-90"></i>Results approvals</h1>
    <p class="page-subtitle-landing mb-0">Imported results wait here until approved — only then can students and guardians see them.</p>
</div>

@unless($canApprove)
<div class="card card-landing">
    <div class="card-body">
        <p class="mb-0 text-muted">Only the Principal or VP (ARC) can approve or reject results. You can still see the pending count below for visibility.</p>
    </div>
</div>
@endunless

@forelse($pending as $row)
@php
    $group = $groups[$row->semester_id] ?? ['grids' => [], 'summary' => ['total' => 0, 'pass' => 0, 'fail' => 0], 'student_summary' => ['total' => 0, 'pass' => 0, 'fail' => 0]];
    $collapseId = 'approval-sem-'.$row->semester_id;
@endphp
<div class="card card-landing mb-3">
    <div
        class="card-header-landing d-flex justify-content-between align-items-center gap-3 py-3 approvals-fold-trigger collapsed"
        data-bs-toggle="collapse"
        data-bs-target="#{{ $collapseId }}"
        aria-expanded="false"
        role="button"
        tabindex="0"
    >
        <div>
            <h2 class="h5 mb-0 fw-semibold">{{ $row->semester?->label ?? '—' }}</h2>
            <div class="small text-muted">{{ $row->row_count }} module result(s) pending &middot; {{ $group['student_summary']['total'] }} student(s)</div>
        </div>
        <div class="d-flex align-items-center gap-4">
            <div class="approval-summary-stat pass"><span class="value">{{ $group['student_summary']['pass'] }}</span><span class="label">Students passed</span></div>
            <div class="approval-summary-stat fail"><span class="value">{{ $group['student_summary']['fail'] }}</span><span class="label">Students failed</span></div>
            @if($canApprove)
            <div class="d-flex gap-2" onclick="event.stopPropagation()">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $row->semester_id }}">Approve</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $row->semester_id }}">Reject</button>
            </div>
            @endif
            <i class="bi bi-chevron-down approvals-fold-icon flex-shrink-0"></i>
        </div>
    </div>
    <div id="{{ $collapseId }}" class="collapse border-top border-light-subtle">
        <div class="card-body">
            @forelse($group['grids'] as $grid)
                @include('results.partials.ca-approval-grid', ['grid' => $grid])
            @empty
                <p class="text-muted small mb-0">No student-level breakdown available for this semester.</p>
            @endforelse
        </div>
    </div>
</div>

@if($canApprove)
<div class="modal fade" id="approveModal{{ $row->semester_id }}" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('results.approvals.approve') }}" method="POST">
            @csrf
            <input type="hidden" name="semester_id" value="{{ $row->semester_id }}">
            <div class="modal-header"><h5 class="modal-title">Approve {{ $row->semester?->label }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">Approving publishes {{ $row->row_count }} result row(s) to students and guardians and queues the release SMS.</p>
                <label class="form-label">SMS template</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sms_template" id="smsCa{{ $row->semester_id }}" value="results_ca" checked>
                    <label class="form-check-label" for="smsCa{{ $row->semester_id }}">CA results</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sms_template" id="smsFinal{{ $row->semester_id }}" value="results_final">
                    <label class="form-check-label" for="smsFinal{{ $row->semester_id }}">Final results</label>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Approve</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="rejectModal{{ $row->semester_id }}" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('results.approvals.reject') }}" method="POST">
            @csrf
            <input type="hidden" name="semester_id" value="{{ $row->semester_id }}">
            <div class="modal-header"><h5 class="modal-title">Reject {{ $row->semester?->label }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="review_notes" class="form-control" rows="3" minlength="5" required placeholder="What needs to be corrected before re-import"></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Reject</button></div>
        </form>
    </div></div>
</div>
@endif
@empty
<div class="card card-landing">
    <div class="card-body text-center text-muted py-5">No results pending approval.</div>
</div>
@endforelse
@endsection
