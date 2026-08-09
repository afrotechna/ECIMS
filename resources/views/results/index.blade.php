@extends('layouts.app')
@section('title', 'Results')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('courses.index') }}">Modules</a>
    <span class="mx-2">/</span>
    <span>Results</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-journal-check me-2 opacity-90"></i>Results</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('results.approvals.index') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-check2-square me-1"></i>Approvals</a>
        <a href="{{ route('results.transcript') }}" class="btn btn-outline-light btn-sm">Transcript</a>
        <a href="{{ route('results.import.ca') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-upload me-1"></i>Import CA (CSV)</a>
        <a href="{{ route('results.import.final') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-upload me-1"></i>Import final (CSV)</a>
    </div>
</div>


<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Semester</label>
                <select name="semester_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($semesters as $s)
                    <option value="{{ $s->id }}" {{ request('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-0">Student</label>
                <select name="student_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($students as $st)
                    <option value="{{ $st->id }}" {{ request('student_id') == $st->id ? 'selected' : '' }}>{{ $st->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button></div>
        </form>
        <hr class="my-3">
        <form method="POST" action="{{ route('results.notify-sms') }}" class="row g-2 align-items-end" onsubmit="event.preventDefault(); var f=this; Swal.fire({title:'Send result SMS?', text:'Send result SMS to all students with results for this semester?', icon:'warning', showCancelButton:true, confirmButtonColor:'#0d6efd', cancelButtonColor:'#6c757d', confirmButtonText:'Send'}).then(function(r){ if(r.isConfirmed) HTMLFormElement.prototype.submit.call(f); });">
            @csrf
            <div class="col-md-4">
                <label class="form-label small mb-0">Notify by SMS (Twilio)</label>
                <select name="semester_id" class="form-select form-select-sm" required>
                    @foreach($semesters as $s)
                    <option value="{{ $s->id }}" {{ request('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="template" value="results_final">
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-phone me-1"></i> Send result SMS</button>
            </div>
        </form>
        <p class="small text-muted mb-0 mt-2">Sends to guardian and student phones on file. Skips students already notified for this semester.</p>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Result list</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Student</th><th>Course</th><th>Semester</th><th class="text-end">CA</th><th class="text-end">Exam</th><th class="text-end">Total</th><th>Grade</th><th>Approval</th><th class="text-end">Lock</th></tr>
            </thead>
            <tbody>
                @forelse($results as $r)
                <tr>
                    <td>{{ $r->student->full_name }}</td>
                    <td>{{ $r->course->code }}</td>
                    <td>{{ $r->semester->label }}</td>
                    <td class="text-end">{{ $r->ca_mark !== null ? number_format($r->ca_mark, 1) : '-' }}</td>
                    <td class="text-end">{{ $r->exam_mark !== null ? number_format($r->exam_mark, 1) : '-' }}</td>
                    <td class="text-end">{{ $r->total_mark !== null ? number_format($r->total_mark, 1) : '-' }}</td>
                    <td><span class="badge bg-{{ $r->grade === 'F' ? 'danger' : 'secondary' }}">{{ $r->grade ?? '-' }}</span></td>
                    <td>
                        @php
                            $statusColor = match($r->status) { 'approved' => 'success', 'rejected' => 'danger', default => 'warning' };
                        @endphp
                        <span class="badge bg-{{ $statusColor }}">{{ \App\Models\Result::STATUSES[$r->status] ?? $r->status }}</span>
                    </td>
                    <td class="text-end">
                        @if($r->is_locked)
                        <form action="{{ route('results.unlock', $r) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-warning me-1">Unlock</button></form>
                        @else
                        <form action="{{ route('results.lock', $r) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-secondary">Lock</button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">No results yet. Use <a href="{{ route('results.import.ca') }}">Import CA</a> or <a href="{{ route('results.import.final') }}">Import final</a> (CSV) for the correct semester.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($results->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $results->links() }}</div>
    @endif
</div>
@endsection
