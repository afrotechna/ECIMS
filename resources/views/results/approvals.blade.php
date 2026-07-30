@extends('layouts.app')
@section('title', 'Results approvals')
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

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Semester</th><th>Pending rows</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($pending as $row)
                    <tr>
                        <td>{{ $row->semester?->label ?? '—' }}</td>
                        <td>{{ $row->row_count }}</td>
                        <td class="text-end">
                            @if($canApprove)
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $row->semester_id }}">Approve</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $row->semester_id }}">Reject</button>

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
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted py-5">No results pending approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
