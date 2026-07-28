@extends('layouts.app')
@php
    $studentPortal = $studentPortal ?? false;
    $indexRoute = $indexRoute ?? 'institution-documents.index';
    $pageTitle = $studentPortal ? 'College documents' : 'Institution documents';
@endphp
@section('title', $folderLabel)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route($indexRoute) }}">{{ $pageTitle }}</a>
    <span class="mx-2">/</span>
    <span>{{ $folderLabel }}</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing">
            <i class="bi {{ \App\Models\InstitutionDocument::folderIcons()[$folderKey] ?? 'bi-folder2' }} me-2 opacity-90"></i>
            {{ $folderLabel }}
        </h1>
        @if($studentPortal)
        <p class="page-subtitle-landing mb-0">Files shared for your programme{{ auth()->user()->student?->programme ? ' ('.auth()->user()->student->programme->code.')' : '' }} and college-wide documents.</p>
        @endif
    </div>
    @if(!$studentPortal)
    @canModule('institution_docs', 'create')
    <a href="{{ route('institution-documents.create', ['category' => $folderKey]) }}" class="btn btn-light btn-sm text-dark">
        <i class="bi bi-upload me-1"></i> Upload to this folder
    </a>
    @endcanModule
    @endif
</div>

<div class="card card-landing mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="category" value="{{ $folderKey }}">
            <div class="col-md-6">
                <label class="form-label small mb-0">Search in folder</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Title or description">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                <a href="{{ route($indexRoute, ['category' => $folderKey]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

@if($studentPortal)
<div class="card card-landing">
    <div class="card-header-landing"><span><i class="bi bi-files me-2"></i>Files</span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Size</th>
                        <th class="text-end">Download</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                    <tr>
                        <td>
                            <strong>{{ $doc->title }}</strong>
                            @if($doc->description)<br><span class="small text-muted">{{ Str::limit($doc->description, 80) }}</span>@endif
                        </td>
                        <td class="small">{{ $doc->created_at->format('d/m/Y') }}</td>
                        <td class="small">{{ number_format($doc->size / 1024, 0) }} KB</td>
                        <td class="text-end">
                            <a href="{{ route('institution-documents.download', $doc) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">No public files in this folder yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($documents->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $documents->links() }}</div>
    @endif
</div>
@else
@php
    $bulkDelete = [
        'bulkModule' => 'institution_docs',
        'bulkAction' => route('institution-documents.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteInstitutionDocs',
        'bulkTableId' => 'institutionDocumentsTable',
        'bulkItemCount' => $documents->count(),
        'bulkHidden' => array_filter(['category' => $folderKey, 'q' => request('q')]),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-files me-2"></i>Files</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="institutionDocumentsTable">
                <thead>
                    <tr>
                        @include('partials.bulk-delete.th', $bulkDelete)
                        <th>Title</th>
                        <th>Programme</th>
                        <th>Uploaded</th>
                        <th>Size</th>
                        <th>Access</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                    <tr>
                        @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $doc->id]))
                        <td>
                            <strong>{{ $doc->title }}</strong>
                            @if($doc->description)<br><span class="small text-muted">{{ Str::limit($doc->description, 80) }}</span>@endif
                            @if($doc->original_name)<br><span class="small text-muted"><i class="bi bi-paperclip"></i> {{ $doc->original_name }}</span>@endif
                        </td>
                        <td class="small">
                            @if($doc->programme_id)
                            <span class="badge bg-light text-dark">{{ $doc->programmeLabel() }}</span>
                            @else
                            <span class="text-muted">All</span>
                            @endif
                        </td>
                        <td class="small">{{ $doc->created_at->format('d/m/Y') }}<br><span class="text-muted">{{ $doc->uploader?->name ?? '—' }}</span></td>
                        <td class="small">{{ number_format($doc->size / 1024, 0) }} KB</td>
                        <td class="text-nowrap">
                            <span class="badge bg-primary">Staff</span>
                            @if($doc->is_public)
                            <span class="badge bg-success">Students</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('institution-documents.download', $doc) }}" class="btn btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                            @canModule('institution_docs', 'update')
                            <a href="{{ route('institution-documents.edit', $doc) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcanModule
                            @canModule('institution_docs', 'delete')
                            <form action="{{ route('institution-documents.destroy', $doc) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete this document?'])
                            </form>
                            @endcanModule
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No files in this folder yet. Upload the first document.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($documents->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $documents->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endif
@endsection
