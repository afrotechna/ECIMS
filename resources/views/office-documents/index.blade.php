@extends('layouts.app')
@section('title', 'e-Office')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>e-Office</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-send-check me-2 opacity-90"></i>e-Office</h1>
        <p class="page-subtitle-landing mb-0">Send documents to colleagues for printing or action, and track where they've reached.</p>
    </div>
    <a href="{{ route('office-documents.create') }}" class="btn btn-cta"><i class="bi bi-plus-lg me-1"></i>Send document</a>
</div>

<ul class="nav nav-pills gap-2 mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'inbox' ? 'active' : '' }}" href="{{ route('office-documents.index', ['tab' => 'inbox']) }}">
            <i class="bi bi-inbox me-1"></i>Inbox
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'sent' ? 'active' : '' }}" href="{{ route('office-documents.index', ['tab' => 'sent']) }}">
            <i class="bi bi-send me-1"></i>Sent
        </a>
    </li>
</ul>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>{{ $tab === 'sent' ? 'To' : 'From' }}</th>
                    <th>Status</th>
                    <th>{{ $tab === 'sent' ? 'Sent' : 'Received' }}</th>
                    <th class="text-center" style="width:5rem">View</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                <tr>
                    <td class="fw-medium">{{ $doc->title }}</td>
                    <td>{{ ($tab === 'sent' ? $doc->recipient : $doc->sender)->name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ match($doc->status) {
                            'sent' => 'bg-secondary',
                            'received' => 'bg-info text-dark',
                            'printed' => 'bg-primary',
                            'completed' => 'bg-success',
                            default => 'bg-secondary',
                        } }}">{{ $doc->statusLabel() }}</span>
                    </td>
                    <td>{{ $doc->created_at->format('d M Y H:i') }}</td>
                    <td class="text-center">
                        <a href="{{ route('office-documents.show', $doc) }}" class="btn btn-outline-primary btn-sm" title="View" aria-label="View">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-5">{{ $tab === 'sent' ? 'You have not sent any documents yet.' : 'Your inbox is empty.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if($documents->hasPages())<div class="mt-3">{{ $documents->links() }}</div>@endif
@endsection
