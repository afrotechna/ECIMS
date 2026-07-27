@extends('layouts.app')
@php
    $studentPortal = $studentPortal ?? false;
    $indexRoute = $indexRoute ?? 'institution-documents.index';
    $pageTitle = $studentPortal ? 'College documents' : 'Institution documents';
@endphp
@section('title', $pageTitle)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>{{ $pageTitle }}</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-folder2-open me-2 opacity-90"></i>{{ $pageTitle }}</h1>
        <p class="page-subtitle-landing mb-0">
            @if($studentPortal)
            Documents for your programme and college-wide files. Open a folder to download.
            @if(auth()->user()->student?->programme)
            <span class="d-block mt-1">Your programme: <strong>{{ auth()->user()->student->programme->code }}</strong></span>
            @endif
            @else
            Official college records stored securely. Open a folder to view or upload files.
            @endif
        </p>
    </div>
</div>

<div class="row g-3">
    @foreach($folders as $folder)
    <div class="col-sm-6 col-md-4 col-lg-3">
        <a href="{{ route($indexRoute, ['category' => $folder['key']]) }}" class="text-decoration-none">
            <div class="card card-landing h-100 institution-doc-folder">
                <div class="card-body text-center py-4">
                    <div class="institution-doc-folder-icon mb-3">
                        <i class="bi {{ $folder['icon'] }}"></i>
                    </div>
                    <h2 class="h6 mb-1 text-dark">{{ $folder['label'] }}</h2>
                    <p class="small text-muted mb-0">
                        {{ $folder['count'] }} {{ Str::plural('file', $folder['count']) }}
                    </p>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

@push('styles')
<style>
.institution-doc-folder { transition: box-shadow .15s, transform .15s; }
.institution-doc-folder:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); transform: translateY(-2px); }
.institution-doc-folder-icon { font-size: 2.5rem; color: var(--cohas-primary, #0d6efd); opacity: .9; }
</style>
@endpush
@endsection
