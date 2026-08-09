@extends('layouts.app')
@section('title', 'Clinical logbook')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('my.clinical.placement') }}">Clinical placement</a>
    <span class="mx-2">/</span>
    <span>Logbook</span>
</nav>

<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-journal-medical me-2 opacity-90"></i>Clinical logbook</h1>
        <p class="page-subtitle-landing mb-0">Record skills and procedures; submit for Clinical Instructor sign-off</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('my.clinical.placement') }}" class="btn btn-outline-secondary btn-sm">My placement</a>
        <a href="{{ route('my.clinical.logbook.print') }}" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Print</a>
        <a href="{{ route('my.clinical.logbook.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New entry</a>
    </div>
</div>


@include('clinical.partials.nta4-practicum-reference')

<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="badge bg-light text-dark">Approved: {{ $logbook_counts['approved'] ?? 0 }}</span>
    <span class="badge bg-warning text-dark">Pending: {{ $logbook_counts['pending'] ?? 0 }}</span>
    <span class="badge bg-secondary">Draft: {{ $logbook_counts['draft'] ?? 0 }}</span>
</div>

<div class="card card-landing">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Procedure / skill</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->performed_on->format('j M Y') }}</td>
                    <td>
                        <span class="fw-semibold">{{ $entry->procedure?->code }}</span>
                        {{ $entry->procedure?->name }}
                    </td>
                    <td class="small">{{ \App\Support\ClinicalRotationCatalog::departmentLabel($entry->department_code ?? '') }}</td>
                    <td><span class="badge {{ \App\Models\ClinicalLogbookEntry::statusBadgeClass($entry->status) }}">{{ \App\Models\ClinicalLogbookEntry::statusLabel($entry->status) }}</span></td>
                    <td class="text-end">
                        @include('partials.action-view', ['href' => route('my.clinical.logbook.show', $entry)])
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No logbook entries yet. <a href="{{ route('my.clinical.logbook.create') }}">Add your first entry</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="card-body pt-0">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
