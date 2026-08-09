@extends('layouts.app')
@section('title', 'Logbook entry')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('my.clinical.logbook.index') }}">Clinical logbook</a>
    <span class="mx-2">/</span>
    <span>Entry</span>
</nav>


<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0">{{ $entry->procedure?->name }}</h1>
        <p class="page-subtitle-landing mb-0">{{ $entry->procedure?->code }} · {{ $entry->performed_on->format('j F Y') }}</p>
    </div>
    <span class="badge fs-6 {{ \App\Models\ClinicalLogbookEntry::statusBadgeClass($entry->status) }}">{{ \App\Models\ClinicalLogbookEntry::statusLabel($entry->status) }}</span>
</div>

<div class="card card-landing mb-3">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><span class="text-muted small">Department</span><div>{{ \App\Support\ClinicalRotationCatalog::departmentLabel($entry->department_code ?? '') }}</div></div>
            <div class="col-md-4"><span class="text-muted small">Case reference</span><div>{{ $entry->case_reference ?: '—' }}</div></div>
            <div class="col-md-4"><span class="text-muted small">Submitted</span><div>{{ $entry->submitted_at?->format('j M Y H:i') ?? '—' }}</div></div>
        </div>
        <h2 class="h6 text-uppercase text-muted">Case summary</h2>
        <p class="mb-3">{{ $entry->case_summary }}</p>
        @if($entry->skills_notes)
            <h2 class="h6 text-uppercase text-muted">Skills performed</h2>
            <p class="mb-0">{{ $entry->skills_notes }}</p>
        @endif
    </div>
</div>

@if($entry->reviewer_feedback)
<div class="card card-landing mb-3 border-warning">
    <div class="card-header-landing bg-warning text-dark">Instructor feedback</div>
    <div class="card-body">{{ $entry->reviewer_feedback }}</div>
</div>
@endif

<div class="d-flex flex-wrap gap-2">
    <a href="{{ route('my.clinical.logbook.index') }}" class="btn btn-outline-secondary">Back to logbook</a>
    @if($entry->isEditableByStudent())
        <a href="{{ route('my.clinical.logbook.edit', $entry) }}" class="btn btn-primary">Edit entry</a>
        @if($entry->status === \App\Models\ClinicalLogbookEntry::STATUS_DRAFT)
            <form method="POST" action="{{ route('my.clinical.logbook.submit', $entry) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">Submit for review</button>
            </form>
        @endif
    @endif
</div>
@endsection
