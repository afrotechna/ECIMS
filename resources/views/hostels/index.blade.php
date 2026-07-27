@extends('layouts.app')
@section('title', 'Hostels')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accommodation</span>
    <span class="mx-2">/</span>
    <span>Hostels</span>
</nav>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-building me-2 opacity-90"></i>Hostels</h1>
        <p class="page-subtitle-landing mb-0">Standard grid: <strong>14 blocks × 4 rooms</strong>, <strong>8 berths</strong> per room (4 double-decker beds).</p>
    </div>
    <a href="{{ route('hostels.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Hostel</a>
</div>

@php
    $bulkDelete = [
        'bulkModule' => 'accommodation',
        'bulkAction' => route('hostels.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteHostels',
        'bulkTableId' => 'hostelsTable',
        'bulkItemCount' => $hostels->count(),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Hostels</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="hostelsTable">
                <thead>
                    <tr>
                        @include('partials.bulk-delete.th', $bulkDelete)
                        <th>Name</th>
                        <th>Code</th>
                        <th>Layout</th>
                        <th class="text-end">Rooms</th>
                        <th class="text-end">Total berths</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hostels as $h)
                    <tr>
                        @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $h->id]))
                        <td><strong>{{ $h->name }}</strong></td>
                        <td>{{ $h->code ?? '—' }}</td>
                        <td class="small">{{ $h->block_count }} blocks × {{ $h->rooms_per_block }} rooms · {{ $h->beds_per_room }} berths/room</td>
                        <td class="text-end">{{ $h->rooms_count }}</td>
                        <td class="text-end">{{ (int) ($h->rooms_sum_bed_count ?? 0) }}</td>
                        <td>@if($h->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                        <td class="text-end">
                            @include('partials.action-edit', ['href' => route('hostels.edit', $h), 'title' => 'Edit / generate', 'class' => 'me-1', 'iconOnly' => true])
                            <a href="{{ route('rooms.index', ['hostel_id' => $h->id]) }}" class="btn btn-sm btn-outline-primary me-1">Rooms</a>
                            @canModule('accommodation', 'delete')
                            <form action="{{ route('hostels.destroy', $h) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete hostel?', 'swalText' => 'Remove all rooms first.'])
                            </form>
                            @endcanModule
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No hostels yet. <a href="{{ route('hostels.create') }}">Add one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($hostels->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $hostels->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
