@extends('layouts.app')
@section('title', 'Accommodation Allocations')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accommodation</span>
    <span class="mx-2">/</span>
    <span>Allocations</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-person-badge me-2 opacity-90"></i>Accommodation Allocations</h1>
        <p class="page-subtitle-landing mb-0">Assign students to rooms and track allocations.</p>
    </div>
    <a href="{{ route('accommodation-allocations.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> New Allocation</a>
</div>

<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-funnel me-2"></i>Filter</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label small mb-0">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="active" {{ ($status ?? 'active') === 'active' ? 'selected' : '' }}>Active only</option>
                    <option value="ended" {{ ($status ?? '') === 'ended' ? 'selected' : '' }}>Ended only</option>
                    <option value="all" {{ ($status ?? '') === 'all' ? 'selected' : '' }}>All (history)</option>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Allocations</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            @php
                $showAllocationActions = auth()->user()->canModule('accommodation', 'update') || auth()->user()->canModule('accommodation', 'create');
            @endphp
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th><th>Reg No</th><th>Room</th><th>Hostel</th><th>From</th><th>To</th><th>Status</th>
                        @if($showAllocationActions)
                        <th class="text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($allocations as $a)
                    <tr>
                        <td>{{ $a->student->full_name }}</td>
                        <td><code>{{ $a->student->reg_no }}</code></td>
                        <td>{{ $a->room->name }}</td>
                        <td>{{ $a->room->hostel->name }}</td>
                        <td>{{ $a->from_date->format('d/m/Y') }}</td>
                        <td>{{ $a->to_date ? $a->to_date->format('d/m/Y') : '-' }}</td>
                        <td>@if($a->status === 'active')<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Ended</span>@endif</td>
                        @if($showAllocationActions)
                        <td class="text-end">
                            @canModule('accommodation', 'update')
                            @include('partials.action-edit', ['href' => route('accommodation-allocations.edit', $a), 'class' => 'me-1', 'iconOnly' => true])
                            @endcanModule
                            @canModule('accommodation', 'create')
                            @if($a->status === 'active')
                            <form action="{{ route('accommodation-allocations.end', $a) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-warning" data-swal-confirm data-swal-title="End this allocation?" data-swal-text="Sets the end date to today.">End</button>
                            </form>
                            @endif
                            @endcanModule
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $showAllocationActions ? 8 : 7 }}" class="text-center text-muted py-5">No allocations yet. <a href="{{ route('accommodation-allocations.create') }}">Create one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($allocations->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $allocations->links() }}</div>
    @endif
</div>
@endsection
