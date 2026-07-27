@extends('layouts.app')
@section('title', 'Edit Announcement')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('announcements.index') }}">Announcements</a>
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>
<div class="page-header-landing mb-3">
    <h1 class="page-title-landing"><i class="bi bi-pencil me-2 opacity-90"></i>Edit announcement</h1>
    <p class="page-subtitle-landing mb-0">{{ $announcement->title }}</p>
</div>
<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-megaphone me-2"></i>Announcement</div>
    <form action="{{ route('announcements.update', $announcement) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('announcements.partials.form-fields', ['announcement' => $announcement, 'programmes' => $programmes])
        </div>
        <div class="card-footer bg-light border-0 d-flex flex-wrap gap-2 py-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save changes</button>
            <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
