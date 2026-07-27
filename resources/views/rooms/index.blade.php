@extends('layouts.app')
@section('title', 'Rooms')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accommodation</span>
    <span class="mx-2">/</span>
    <span>Rooms</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-door-open me-2 opacity-90"></i>Rooms</h1>
        <p class="page-subtitle-landing mb-0">Room codes use <code>{{ \App\Models\Room::ROOM_CODE_PREFIX }}-Block01-R1</code> when you pick Block + Room. Berths = student spaces (4 double-decker beds = 8).</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('rooms.occupancy', request()->filled('hostel_id') ? ['hostel_id' => request('hostel_id')] : []) }}" class="btn btn-outline-light btn-sm border"><i class="bi bi-people me-1"></i> Live by room</a>
        <a href="{{ route('rooms.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Room</a>
    </div>
</div>

@if($hostels->isNotEmpty())
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-funnel me-2"></i>Filter by hostel</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="hostel_id" class="form-label small mb-0">Filter by hostel</label>
                <select name="hostel_id" id="hostel_id" class="form-select form-select-sm w-auto">
                    <option value="">All hostels</option>
                    @foreach($hostels as $h)
                    <option value="{{ $h->id }}" {{ request('hostel_id') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
        </form>
    </div>
</div>
@endif
@php
    $bulkDelete = [
        'bulkModule' => 'accommodation',
        'bulkAction' => route('rooms.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteRooms',
        'bulkTableId' => 'roomsTable',
        'bulkItemCount' => $rooms->count(),
        'bulkHidden' => array_filter(['hostel_id' => request('hostel_id')]),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Rooms</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="roomsTable">
            <thead>
                <tr>
                    @include('partials.bulk-delete.th', $bulkDelete)
                    <th>Block</th><th>Room code</th><th>Berths</th><th>Status</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $r)
                @php
                    $bm = $blockMeta[$loop->index] ?? ['show' => true, 'rowspan' => 1];
                @endphp
                <tr>
                    @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $r->id]))
                    @if($bm['show'])
                    <td rowspan="{{ $bm['rowspan'] }}" class="bg-light align-middle">{{ $r->blockLabel() ?? '—' }}</td>
                    @endif
                    <td><strong class="font-monospace small">{{ $r->name }}</strong></td>
                    <td>{{ $r->bed_count }}</td>
                    <td>@if($r->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('rooms.edit', $r) }}" class="btn btn-sm btn-cohas-edit" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                        @canModule('accommodation', 'delete')
                        <form action="{{ route('rooms.destroy', $r) }}" method="POST" class="d-inline ms-1">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-sm btn-cohas-delete" title="Delete" aria-label="Delete" data-swal-confirm data-swal-title="Delete room?" data-swal-icon="warning"><i class="bi bi-trash-fill" aria-hidden="true"></i></button>
                        </form>
                        @endcanModule
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No rooms yet. Add one or create a hostel first.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($rooms->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $rooms->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
