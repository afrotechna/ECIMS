@extends('layouts.app')
@section('title', 'Announcements')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Announcements</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-megaphone me-2 opacity-90"></i>Announcements</h1>
        <p class="page-subtitle-landing mb-0">Notices and updates for students and staff.</p>
    </div>
    <a href="{{ route('announcements.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> New</a>
</div>
@php
    $bulkDelete = [
        'bulkModule' => 'college_comms',
        'bulkAction' => route('announcements.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteAnnouncements',
        'bulkScopeId' => 'announcementsList',
        'bulkItemCount' => $announcements->count(),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Announcements</span>
        <div class="d-flex align-items-center gap-2 no-print">
            @canModule('college_comms', 'delete')
                @if($announcements->count() > 0)
                    <input type="checkbox" class="form-check-input bulk-delete-select-all" data-bulk-scope="announcementsList" aria-label="Select all announcements on this page">
                    <span class="small text-muted">Select all</span>
                @endif
            @endcanModule
            @include('partials.bulk-delete.toolbar', $bulkDelete)
        </div>
    </div>
    <div class="card-body" id="announcementsList" data-bulk-delete-scope>
        @forelse($announcements as $a)
        <div class="border-bottom pb-3 mb-3 d-flex gap-2 align-items-start">
            @include('partials.bulk-delete.checkbox-inline', array_merge($bulkDelete, ['bulkRowId' => $a->id]))
            <div class="flex-grow-1">
            <h6 class="mb-1">{{ $a->title }}</h6>
            <span class="badge bg-light text-dark border small">{{ $a->targetingLabel() }}</span>
            @if($a->body)<p class="text-muted small mb-1 mt-1">{{ \Illuminate\Support\Str::limit(strip_tags($a->body), 200) }}</p>@endif
            <small class="text-muted">{{ $a->created_at->format('d/m/Y H:i') }} @if($a->show_until) &middot; Show until {{ $a->show_until->format('d/m/Y') }}@endif</small>
            @canModule('college_comms', 'delete')
            <form action="{{ route('announcements.destroy', $a) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this announcement?');">
                @csrf
                @method('DELETE')
                @include('partials.action-delete', ['submit' => true, 'class' => 'ms-1'])
            </form>
            @endcanModule
            @include('partials.action-edit', ['href' => route('announcements.edit', $a), 'class' => 'ms-1', 'iconOnly' => true])
            </div>
        </div>
        @empty
        <p class="text-muted mb-0">No announcements yet.</p>
        @endforelse
    </div>
    @if($announcements->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $announcements->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
