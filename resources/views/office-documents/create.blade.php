@extends('layouts.app')
@section('title', 'Send document')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('office-documents.index') }}">e-Office</a>
    <span class="mx-2">/</span>
    <span>Send document</span>
</nav>
<div class="page-header-landing mb-3">
    <h1 class="page-title-landing"><i class="bi bi-send-plus me-2 opacity-90"></i>{{ $replyTo ? 'Reply' : 'Send document' }}</h1>
</div>

@if($replyTo)
<div class="alert alert-light border mb-3">
    Replying to <strong>{{ $replyTo->title }}</strong> from {{ $replyTo->sender->name ?? '' }}.
</div>
@endif

<div class="card card-landing">
    <div class="card-body">
        <form method="POST" action="{{ route('office-documents.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @if($replyTo)
            <input type="hidden" name="parent_id" value="{{ $replyTo->id }}">
            @endif
            <div class="col-md-6">
                <label class="form-label">Recipient <span class="text-danger">*</span></label>
                <select name="recipient_id" class="form-select @error('recipient_id') is-invalid @enderror" required>
                    <option value="">— Select recipient —</option>
                    @foreach($recipients as $r)
                    <option value="{{ $r->id }}" {{ (string) old('recipient_id', $replyTo?->sender_id) === (string) $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ \App\Models\User::roleLabel($r->role) }})</option>
                    @endforeach
                </select>
                @error('recipient_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" maxlength="255" required value="{{ old('title', $replyTo ? 'RE: '.$replyTo->title : '') }}">
                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Note</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="5000">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Document <span class="text-danger">*</span></label>
                <input type="file" name="document" class="form-control @error('document') is-invalid @enderror" required>
                <div class="form-text">Max 20 MB.</div>
                @error('document')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-cta"><i class="bi bi-send me-1"></i>Send</button>
                <a href="{{ route('office-documents.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
