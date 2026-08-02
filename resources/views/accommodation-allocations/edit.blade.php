@extends('layouts.app')

@section('title', 'Edit Allocation')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('accommodation-allocations.index') }}">Accommodation · Allocations</a>
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit Allocation</h1>
    <p class="page-subtitle-landing mb-0">{{ $allocation->student->full_name }} — {{ $allocation->room->name }}</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-person-badge me-2"></i>Allocation details</div>
    <div class="card-body">
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        <form action="{{ route('accommodation-allocations.update', $allocation) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student</label>
                    <select class="form-select @error('student_id') is-invalid @enderror" id="student_id" name="student_id" required>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ old('student_id', $allocation->student_id) == $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                    @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="room_id" class="form-label">Room</label>
                    <select class="form-select @error('room_id') is-invalid @enderror" id="room_id" name="room_id" required data-no-search>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}" {{ old('room_id', $allocation->room_id) == $r->id ? 'selected' : '' }}>{{ $r->hostel->name }} — {{ $r->name }} ({{ $r->remainingBerths() }} remaining)</option>
                        @endforeach
                    </select>
                    <div class="form-text">Rooms that are fully booked (all berths have active allocations) are hidden, except the room for this allocation. Changing the student clears the room choice and reloads the list.</div>
                    @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="from_date" class="form-label">From date</label>
                    <input type="date" class="form-control @error('from_date') is-invalid @enderror" id="from_date" name="from_date" value="{{ old('from_date', $allocation->from_date->format('Y-m-d')) }}" required>
                    @error('from_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="to_date" class="form-label">To date</label>
                    <input type="date" class="form-control @error('to_date') is-invalid @enderror" id="to_date" name="to_date" value="{{ old('to_date', $allocation->to_date ? $allocation->to_date->format('Y-m-d') : '') }}">
                    @error('to_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="active" {{ old('status', $allocation->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="ended" {{ old('status', $allocation->status) === 'ended' ? 'selected' : '' }}>Ended</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update</button>
                <a href="{{ route('accommodation-allocations.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var studentEl = document.getElementById('student_id');
    var roomEl = document.getElementById('room_id');
    var url = @json($roomOptionsUrl);
    if (!studentEl || !roomEl || !url) return;

    function placeholder() {
        var o = document.createElement('option');
        o.value = '';
        o.textContent = 'Select room';
        return o;
    }

    function fillRooms(rooms, preserveId) {
        roomEl.innerHTML = '';
        roomEl.appendChild(placeholder());
        (rooms || []).forEach(function (r) {
            var o = document.createElement('option');
            o.value = String(r.id);
            o.textContent = r.label;
            roomEl.appendChild(o);
        });
        var ps = preserveId != null && preserveId !== '' ? String(preserveId) : '';
        if (ps && [].some.call(roomEl.options, function (opt) { return opt.value === ps; })) {
            roomEl.value = ps;
        }
    }

    function loadRooms(preserveId) {
        fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (data) { fillRooms(data.rooms, preserveId); })
            .catch(function () {});
    }

    studentEl.addEventListener('change', function () {
        loadRooms(null);
    });
})();
</script>
@endpush
