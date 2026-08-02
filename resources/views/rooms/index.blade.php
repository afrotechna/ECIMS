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
        @canModule('accommodation_facilities', 'create')
        <a href="{{ route('rooms.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Room</a>
        @endcanModule
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
        'bulkModule' => 'accommodation_facilities',
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
        @php
            $showRoomActions = auth()->user()->canModule('accommodation_facilities', 'update') || auth()->user()->canModule('accommodation_facilities', 'delete');
        @endphp
        <table class="table table-hover align-middle mb-0" id="roomsTable">
            <thead>
                <tr>
                    @include('partials.bulk-delete.th', $bulkDelete)
                    <th>Block</th><th>Room code</th><th>Berths</th><th>Status</th>
                    @if($showRoomActions)
                    <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            @forelse($roomGroups as $group)
            @php
                $isBlock = $group['blockNumber'] !== null;
                $collapseId = 'roomGroup-'.\Illuminate\Support\Str::slug($group['key']);
                $totalBeds = collect($group['rooms'])->sum('bed_count');
            @endphp
            @if($isBlock)
            <tbody>
                <tr class="cohas-accordion-row" role="button" tabindex="0" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                    <td colspan="100%">
                        <i class="bi bi-chevron-right room-group-caret me-2"></i>
                        <strong>{{ $group['hostel']?->name }} · Block {{ $group['blockNumber'] }}</strong>
                        <span class="text-muted small ms-2">{{ count($group['rooms']) }} room{{ count($group['rooms']) === 1 ? '' : 's' }} · {{ $totalBeds }} berths</span>
                    </td>
                </tr>
            </tbody>
            <tbody class="collapse" id="{{ $collapseId }}">
            @else
            <tbody>
            @endif
                @foreach($group['rooms'] as $r)
                <tr>
                    @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $r->id]))
                    @if($loop->first)
                    <td class="bg-light align-middle" rowspan="{{ count($group['rooms']) }}">{{ $r->blockLabel() ?? '—' }}</td>
                    @endif
                    <td><strong class="font-monospace small">{{ $r->name }}</strong></td>
                    <td>{{ $r->bed_count }}</td>
                    <td>@if($r->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                    @if($showRoomActions)
                    <td class="text-end text-nowrap">
                        @canModule('accommodation_facilities', 'update')
                        @include('partials.action-edit', ['href' => route('rooms.edit', $r), 'iconOnly' => true])
                        @endcanModule
                        @canModule('accommodation_facilities', 'delete')
                        <form action="{{ route('rooms.destroy', $r) }}" method="POST" class="d-inline ms-1">
                            @csrf
                            @method('DELETE')
                            @include('partials.action-delete', ['swalTitle' => 'Delete room?'])
                        </form>
                        @endcanModule
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            @empty
            <tbody>
                <tr><td colspan="{{ $showRoomActions ? 6 : 5 }}" class="text-center text-muted py-5">No rooms yet. Add one or create a hostel first.</td></tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </div>
    @if($rooms->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $rooms->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@push('styles')
<style>
    .cohas-accordion-row { cursor: pointer; }
    .cohas-accordion-row:hover { background-color: var(--cohas-hover, rgba(0,0,0,.03)); }
    .room-group-caret { transition: transform .15s ease; display: inline-block; }
    .cohas-accordion-row[aria-expanded="true"] .room-group-caret { transform: rotate(90deg); }
</style>
@endpush
@endsection
