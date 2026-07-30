@extends('layouts.app')
@section('title', 'Edit Room')
@section('content')
@php
    $oldBlock = old('block_number', $room->block_number);
    $oldRoom = old('room_in_block', $room->room_in_block);
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('rooms.index') }}">Accommodation · Rooms</a>
    <span class="mx-2">/</span>
    <span>Edit {{ $room->name }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit Room</h1>
    <p class="page-subtitle-landing mb-0">{{ $room->hostel->name ?? '' }} — {{ $room->name }}</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil me-2"></i>Room details</div>
    <div class="card-body">
        <form action="{{ route('rooms.update', $room) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="hostel_id" class="form-label">Hostel <span class="text-danger">*</span></label>
                    <select class="form-select @error('hostel_id') is-invalid @enderror" id="hostel_id" name="hostel_id" required>
                        @foreach($hostels as $h)
                        <option value="{{ $h->id }}" {{ (string) old('hostel_id', $room->hostel_id) === (string) $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                    @error('hostel_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="block_number" class="form-label">Block</label>
                    <select class="form-select @error('block_number') is-invalid @enderror" id="block_number" name="block_number" data-no-search>
                        <option value="">— Select block —</option>
                    </select>
                    @error('block_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="room_in_block" class="form-label">Room in block</label>
                    <select class="form-select @error('room_in_block') is-invalid @enderror" id="room_in_block" name="room_in_block" data-no-search>
                        <option value="">— Select room —</option>
                    </select>
                    @error('room_in_block')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="bed_count" class="form-label">Berths <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('bed_count') is-invalid @enderror" id="bed_count" name="bed_count" value="{{ old('bed_count', $room->bed_count) }}" min="1" max="32" required>
                    @error('bed_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="room_name_custom" class="form-label">Custom room name <span class="text-muted fw-normal">(only if not using block + room)</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="room_name_custom" name="name" value="{{ old('name', $room->block_number === null ? $room->name : '') }}" maxlength="50" placeholder="Leave empty when Block and Room are selected — code is {{ \App\Models\Room::ROOM_CODE_PREFIX }}-BlockNN-RN">
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $room->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Room</button>
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const layouts = @json($hostelLayouts);
    const hostelSel = document.getElementById('hostel_id');
    const blockSel = document.getElementById('block_number');
    const roomSel = document.getElementById('room_in_block');
    const oldBlock = @json($oldBlock);
    const oldRoom = @json($oldRoom);

    function layoutForHostel(id) {
        const n = parseInt(id, 10);
        return layouts.find(function (l) { return l.id === n; });
    }

    function rebuildBlockRoom() {
        const cfg = layoutForHostel(hostelSel.value);
        const preserveBlock = blockSel.value;
        const preserveRoom = roomSel.value;
        blockSel.innerHTML = '<option value="">— Select block —</option>';
        roomSel.innerHTML = '<option value="">— Select room —</option>';
        if (!cfg) return;
        for (let b = 1; b <= cfg.blocks; b++) {
            const o = document.createElement('option');
            o.value = String(b);
            o.textContent = 'Block ' + b;
            blockSel.appendChild(o);
        }
        for (let r = 1; r <= cfg.roomsPerBlock; r++) {
            const o = document.createElement('option');
            o.value = String(r);
            o.textContent = 'Room ' + r;
            roomSel.appendChild(o);
        }
        if (preserveBlock && parseInt(preserveBlock, 10) <= cfg.blocks) blockSel.value = preserveBlock;
        if (preserveRoom && parseInt(preserveRoom, 10) <= cfg.roomsPerBlock) roomSel.value = preserveRoom;
    }

    hostelSel.addEventListener('change', rebuildBlockRoom);
    document.addEventListener('DOMContentLoaded', function () {
        rebuildBlockRoom();
        if (oldBlock !== null && oldBlock !== '' && String(oldBlock) !== 'null') {
            blockSel.value = String(oldBlock);
        }
        if (oldRoom !== null && oldRoom !== '' && String(oldRoom) !== 'null') {
            roomSel.value = String(oldRoom);
        }
    });
})();
</script>
@endpush
@endsection
