@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Notifications</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-bell me-2 opacity-90"></i>Notifications</h1>
        <p class="page-subtitle-landing mb-0">Read recent updates and workflow alerts.</p>
    </div>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button type="submit" class="btn btn-light btn-sm text-dark"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
    </form>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse($notifications as $n)
            <a href="{{ $n->data['url'] ?? route('notifications.index') }}" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-semibold">{{ $n->data['title'] ?? 'Notification' }}</div>
                        <div class="small text-muted">{{ $n->data['message'] ?? '' }}</div>
                    </div>
                    <div class="text-end">
                        @if(!$n->read_at)
                        <span class="badge bg-primary">New</span>
                        @endif
                        <div class="small text-muted mt-1">{{ $n->created_at?->diffForHumans() }}</div>
                    </div>
                </div>
            </a>
            @empty
            <div class="p-5 text-center text-muted">No notifications yet.</div>
            @endforelse
        </div>
    </div>
    @if($notifications->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection

