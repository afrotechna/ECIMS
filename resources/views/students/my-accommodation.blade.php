@extends('layouts.app')
@section('title', 'My Accommodation')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>My Accommodation</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-building me-2 opacity-90"></i>My Accommodation</h1>
    <p class="page-subtitle-landing mb-0">{{ $student->full_name }}</p>
</div>
@if($allocation)
<div class="card card-landing">
    <div class="card-header-landing">Current allocation</div>
    <div class="card-body">
        <p class="mb-1"><strong>Hostel:</strong> {{ $allocation->room->hostel->name ?? '-' }}</p>
        <p class="mb-1"><strong>Room:</strong> {{ $allocation->room->name ?? '-' }}</p>
        <p class="mb-0"><strong>From:</strong> {{ $allocation->from_date->format('d/m/Y') }} <strong>To:</strong> {{ $allocation->to_date ? $allocation->to_date->format('d/m/Y') : 'Ongoing' }}</p>
    </div>
</div>
@else
<div class="card card-landing">
    <div class="card-body text-center text-muted py-5">
        <p class="mb-0">No current accommodation allocation. Contact the accommodation office.</p>
    </div>
</div>
@endif
@endsection
