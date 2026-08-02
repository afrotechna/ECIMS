@extends('layouts.app')

@section('title', 'New Allocation')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('accommodation-allocations.index') }}">Accommodation · Allocations</a>
    <span class="mx-2">/</span>
    <span>New</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>New Accommodation Allocation</h1>
    <p class="page-subtitle-landing mb-0">Assign a student to a room with from/to dates.</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-person-badge me-2"></i>Allocation details</div>
    <div class="card-body">
        <form action="{{ route('accommodation-allocations.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                    <select class="form-select @error('student_id') is-invalid @enderror" id="student_id" name="student_id" required>
                        <option value="">Select student</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ old('student_id') == $s->id ? 'selected' : '' }}>{{ $s->reg_no }} — {{ $s->full_name }}</option>
                        @endforeach
                    </select>
                    @if($students->isEmpty())
                    <div class="form-text text-warning">No students have completed registration yet. A student appears here once their semester registration is approved and finished.</div>
                    @else
                    <div class="form-text">Only students who have completed a semester registration are listed.</div>
                    @endif
                    @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="room_id" class="form-label">Room <span class="text-danger">*</span></label>
                    <select class="form-select @error('room_id') is-invalid @enderror" id="room_id" name="room_id" required data-no-search>
                        <option value="">Select room</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}" {{ old('room_id') == $r->id ? 'selected' : '' }}>{{ $r->hostel->name }} — {{ $r->name }} ({{ $r->remainingBerths() }} remaining)</option>
                        @endforeach
                    </select>
                    @if($rooms->isEmpty())
                    <div class="form-text text-warning">No rooms have a free bed (all active allocations are full). Use <a href="{{ route('rooms.occupancy') }}">Live by room</a> to review occupancy, or end an allocation first.</div>
                    @else
                    <div class="form-text">Only rooms with at least one free bed are listed. A room disappears from this list once every berth has an active allocation. Changing the student clears the room choice so a previous room is not left selected.</div>
                    @endif
                    @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="from_date" class="form-label">From date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('from_date') is-invalid @enderror" id="from_date" name="from_date" value="{{ old('from_date', date('Y-m-d')) }}" required>
                    @error('from_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="to_date" class="form-label">To date</label>
                    <input type="date" class="form-control @error('to_date') is-invalid @enderror" id="to_date" name="to_date" value="{{ old('to_date') }}">
                    @error('to_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Create Allocation</button>
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
