@extends('layouts.app')
@section('title', $document->title)
@push('styles')
<style>
    .doc-timeline { list-style: none; padding-left: 0; margin: 0; }
    .doc-timeline li { position: relative; padding-left: 2rem; padding-bottom: 1.25rem; }
    .doc-timeline li:last-child { padding-bottom: 0; }
    .doc-timeline li::before {
        content: ''; position: absolute; left: .3rem; top: .2rem; width: .75rem; height: .75rem;
        border-radius: 50%; background: #cbd5e1; border: 2px solid #fff; box-shadow: 0 0 0 2px #cbd5e1;
    }
    .doc-timeline li.done::before { background: #16a34a; box-shadow: 0 0 0 2px #16a34a; }
    .doc-timeline li::after {
        content: ''; position: absolute; left: .625rem; top: 1rem; bottom: 0; width: 2px; background: #e2e8f0;
    }
    .doc-timeline li:last-child::after { display: none; }
    .doc-timeline .stage-label { font-weight: 600; }
    .doc-timeline .stage-meta { font-size: .8125rem; color: #64748b; }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('office-documents.index') }}">e-Office</a>
    <span class="mx-2">/</span>
    <span>{{ $document->title }}</span>
</nav>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card card-landing mb-3">
            <div class="card-header-landing d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-text me-2"></i>{{ $document->title }}</span>
                <span class="badge {{ match($document->status) {
                    'sent' => 'bg-secondary',
                    'received' => 'bg-info text-dark',
                    'printed' => 'bg-primary',
                    'completed' => 'bg-success',
                    default => 'bg-secondary',
                } }}">{{ $document->statusLabel() }}</span>
            </div>
            <div class="card-body">
                <dl class="row small mb-3">
                    <dt class="col-4 text-muted">From</dt>
                    <dd class="col-8">{{ $document->sender->name ?? '—' }}</dd>
                    <dt class="col-4 text-muted">To</dt>
                    <dd class="col-8">{{ $document->recipient->name ?? '—' }}</dd>
                    @if($document->parent)
                    <dt class="col-4 text-muted">In reply to</dt>
                    <dd class="col-8"><a href="{{ route('office-documents.show', $document->parent) }}">{{ $document->parent->title }}</a></dd>
                    @endif
                </dl>
                @if($document->notes)
                <p class="mb-3">{{ $document->notes }}</p>
                @endif
                <a href="{{ route('office-documents.download', $document) }}" class="btn btn-outline-primary">
                    <i class="bi bi-download me-1"></i>Download {{ $document->original_name }}
                </a>

                <hr class="my-3">
                <div class="d-flex flex-wrap gap-2">
                    @if(auth()->id() === $document->recipient_id)
                        @if($document->received_at && ! $document->printed_at)
                        <form method="POST" action="{{ route('office-documents.mark-printed', $document) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Mark printed</button>
                        </form>
                        @endif
                        @if($document->printed_at && ! $document->completed_at)
                        <form method="POST" action="{{ route('office-documents.mark-completed', $document) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check2-circle me-1"></i>Mark completed</button>
                        </form>
                        @endif
                        <a href="{{ route('office-documents.create', ['reply_to' => $document->id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-reply me-1"></i>Reply</a>
                    @elseif(auth()->id() === $document->sender_id)
                        <a href="{{ route('office-documents.create', ['reply_to' => $document->id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-reply me-1"></i>Send another</a>
                    @endif
                </div>
            </div>
        </div>

        @if($document->replies->isNotEmpty())
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-arrow-return-right me-2"></i>Replies</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($document->replies as $reply)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('office-documents.show', $reply) }}">{{ $reply->title }}</a>
                        <span class="badge bg-light text-dark">{{ $reply->statusLabel() }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-clock-history me-2"></i>Timeline</div>
            <div class="card-body">
                <ul class="doc-timeline">
                    @foreach($document->timeline() as $stage)
                    <li class="{{ $stage['done'] ? 'done' : '' }}">
                        <div class="stage-label">{{ $stage['label'] }}</div>
                        @if($stage['done'] && $stage['at'])
                        <div class="stage-meta">{{ $stage['at']->format('d M Y H:i') }}{{ $stage['by'] ? ' — '.$stage['by'] : '' }}</div>
                        @else
                        <div class="stage-meta">Pending</div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
