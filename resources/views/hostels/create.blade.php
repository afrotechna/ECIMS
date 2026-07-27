@extends('layouts.app')
@section('title', 'Add Hostel')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('hostels.index') }}">Accommodation · Hostels</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add Hostel</h1>
    <p class="page-subtitle-landing mb-0">Default layout matches your campus: <strong>14 blocks</strong>, <strong>4 rooms per block</strong>, <strong>8 berths per room</strong> (4 double-decker beds × 2 tiers). Adjust if needed, then optionally generate all rooms in one step.</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Hostel details</div>
    <div class="card-body">
        <form action="{{ route('hostels.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="e.g. MCH">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12"><hr class="my-1"><span class="form-section-title d-block">Physical layout</span></div>
                <div class="col-md-4">
                    <label for="block_count" class="form-label">Blocks <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('block_count') is-invalid @enderror" id="block_count" name="block_count" value="{{ old('block_count', \App\Models\Hostel::DEFAULT_BLOCK_COUNT) }}" min="1" max="60" required>
                    @error('block_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="rooms_per_block" class="form-label">Rooms per block <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('rooms_per_block') is-invalid @enderror" id="rooms_per_block" name="rooms_per_block" value="{{ old('rooms_per_block', \App\Models\Hostel::DEFAULT_ROOMS_PER_BLOCK) }}" min="1" max="30" required>
                    @error('rooms_per_block')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="beds_per_room" class="form-label">Berths per room <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('beds_per_room') is-invalid @enderror" id="beds_per_room" name="beds_per_room" value="{{ old('beds_per_room', \App\Models\Hostel::DEFAULT_BEDS_PER_ROOM) }}" min="1" max="32" required>
                    <div class="form-text">4 double-decker beds = 8 student spaces.</div>
                    @error('beds_per_room')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="generate_rooms" name="generate_rooms" value="1" {{ old('generate_rooms', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="generate_rooms">Create all rooms now (blocks × rooms; each with the berth count above)</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Hostel</button>
                <a href="{{ route('hostels.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
