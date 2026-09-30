@extends('layouts.app')

@section('title', 'Edit Hostel')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('hostels.index') }}">Accommodation · Hostels</a>
    <span class="mx-2">/</span>
    <span>Edit {{ $hostel->name }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit Hostel</h1>
    <p class="page-subtitle-landing mb-0">{{ $hostel->name }} — {{ $hostel->code ?? 'No code' }} · {{ $hostel->rooms_count }} room(s) on file</p>
</div>


<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-pencil me-2"></i>Hostel details</div>
    <div class="card-body">
        <form action="{{ route('hostels.update', $hostel) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $hostel->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $hostel->code) }}">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                        <option value="">Choose…</option>
                        <option value="male" {{ old('gender', $hostel->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $hostel->gender) === 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    <div class="form-text">Only students of this gender can be allocated to rooms in this hostel.</div>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12"><hr class="my-1"><span class="small text-muted fw-semibold text-uppercase">Physical layout</span></div>
                <div class="col-md-4">
                    <label for="block_count" class="form-label">Blocks <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('block_count') is-invalid @enderror" id="block_count" name="block_count" value="{{ old('block_count', $hostel->block_count) }}" min="1" max="60" required>
                    @error('block_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="rooms_per_block" class="form-label">Rooms per block <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('rooms_per_block') is-invalid @enderror" id="rooms_per_block" name="rooms_per_block" value="{{ old('rooms_per_block', $hostel->rooms_per_block) }}" min="1" max="30" required>
                    @error('rooms_per_block')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="beds_per_room" class="form-label">Berths per room <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('beds_per_room') is-invalid @enderror" id="beds_per_room" name="beds_per_room" value="{{ old('beds_per_room', $hostel->beds_per_room) }}" min="1" max="32" required>
                    <div class="form-text">Used when generating rooms (4 double-deckers = 8).</div>
                    @error('beds_per_room')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $hostel->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Hostel</button>
                <a href="{{ route('hostels.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-grid-3x3-gap me-2"></i>Generate standard rooms</div>
    <div class="card-body">
        @if($hostel->rooms_count > 0)
            <p class="small text-muted mb-2">This hostel already has <strong>{{ $hostel->rooms_count }}</strong> room record(s). Generation is only available when there are <strong>no rooms</strong> (delete unused rooms first; you cannot delete rooms with active allocations).</p>
            <a href="{{ route('rooms.index', ['hostel_id' => $hostel->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-door-open me-1"></i> View rooms</a>
        @else
            <p class="small text-muted mb-3">Creates <strong>{{ $hostel->block_count }} × {{ $hostel->rooms_per_block }} = {{ $hostel->expectedRoomSlots() }}</strong> rooms with codes from <code>{{ \App\Models\Room::generatedCode(1, 1) }}</code> through <code>{{ \App\Models\Room::generatedCode($hostel->block_count, $hostel->rooms_per_block) }}</code>, each with <strong>{{ $hostel->beds_per_room }}</strong> berths.</p>
            <form action="{{ route('hostels.generate-rooms', $hostel) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-success btn-sm" data-swal-confirm data-swal-title="Generate rooms?" data-swal-text="Create {{ $hostel->expectedRoomSlots() }} rooms for this hostel." data-swal-icon="question"><i class="bi bi-magic me-1"></i> Generate all rooms</button>
            </form>
        @endif
    </div>
</div>
@endsection
