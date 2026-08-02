@extends('layouts.app')
@section('title', 'Clinical logbook review')
@push('styles')
<style>
    .logbook-review-table thead th { font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: #475569; white-space: nowrap; }
    .logbook-review-table td { font-size: .8125rem; vertical-align: middle; }
    .logbook-review-table td .fw-semibold { font-size: .8125rem; }
    .logbook-review-table td .small { font-size: .7rem; }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Clinical logbook review</span>
</nav>

<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-patch-check me-2 opacity-90"></i>Clinical logbook review</h1>
        <p class="page-subtitle-landing mb-0">Clinical Instructor sign-off on student procedures and skills</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('clinical-procedures.index') }}" class="btn btn-outline-secondary btn-sm">Procedures</a>
        <a href="{{ route('clinical.framework') }}" class="btn btn-outline-secondary btn-sm">Framework</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<ul class="nav nav-pills mb-3 flex-wrap gap-1">
    <li class="nav-item"><a class="nav-link {{ ($status ?? '') === 'submitted' ? 'active' : '' }}" href="{{ route('clinical-logbook.index', ['status' => 'submitted']) }}">Awaiting review ({{ $counts['submitted'] }})</a></li>
    <li class="nav-item"><a class="nav-link {{ ($status ?? '') === 'approved' ? 'active' : '' }}" href="{{ route('clinical-logbook.index', ['status' => 'approved']) }}">Approved ({{ $counts['approved'] }})</a></li>
    <li class="nav-item"><a class="nav-link {{ ($status ?? '') === 'rejected' ? 'active' : '' }}" href="{{ route('clinical-logbook.index', ['status' => 'rejected']) }}">Returned ({{ $counts['rejected'] }})</a></li>
    <li class="nav-item"><a class="nav-link {{ ($status ?? '') === 'all' ? 'active' : '' }}" href="{{ route('clinical-logbook.index', ['status' => 'all']) }}">All</a></li>
</ul>

<div class="card card-landing">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle logbook-review-table">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Procedure</th>
                    <th>Date</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $entry->student->full_name }}</div>
                        <div class="small text-muted">{{ $entry->student->programme?->code }}</div>
                    </td>
                    <td>{{ $entry->procedure?->code }} — {{ $entry->procedure?->name }}</td>
                    <td>{{ $entry->performed_on->format('j M Y') }}</td>
                    <td class="small">{{ $entry->submitted_at?->format('j M Y H:i') ?? '—' }}</td>
                    <td><span class="badge {{ \App\Models\ClinicalLogbookEntry::statusBadgeClass($entry->status) }}">{{ \App\Models\ClinicalLogbookEntry::statusLabel($entry->status) }}</span></td>
                    <td><a href="{{ route('clinical-logbook.show', $entry) }}" class="btn btn-sm btn-primary">Review</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No entries in this list.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())<div class="card-body">{{ $entries->links() }}</div>@endif
</div>
@endsection
