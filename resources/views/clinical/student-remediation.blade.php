@extends('layouts.app')
@section('title', 'Clinical remediation')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('my.clinical.placement') }}">Clinical training</a>
    <span class="mx-2">/</span>
    <span>Remediation</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing mb-0">Remediation plans</h1>
    <p class="page-subtitle-landing mb-0">Tasks assigned by your Clinical Instructor or coordinator</p>
</div>

<div class="card card-landing">
    @forelse($plans as $plan)
    <div class="card-body border-bottom">
        <div class="d-flex justify-content-between gap-2">
            <div>
                <h2 class="h6 mb-1">{{ $plan->title }}</h2>
                <p class="small mb-1">{{ $plan->description }}</p>
                @if($plan->due_date)<p class="small text-muted mb-0">Due: {{ $plan->due_date->format('j M Y') }}</p>@endif
            </div>
            <span class="badge {{ $plan->status === 'open' ? 'bg-warning text-dark' : 'bg-success' }}">{{ \App\Models\ClinicalRemediationPlan::statusLabel($plan->status) }}</span>
        </div>
        @if($plan->logbookEntry)
            <a href="{{ route('my.clinical.logbook.show', $plan->logbookEntry) }}" class="btn btn-sm btn-outline-primary mt-2">Related logbook entry</a>
        @endif
    </div>
    @empty
    <div class="card-body text-muted">No remediation plans assigned.</div>
    @endforelse
</div>
@if($plans->hasPages())<div class="mt-2">{{ $plans->links() }}</div>@endif
@endsection
