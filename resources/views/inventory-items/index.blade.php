@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Inventory</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-box-seam me-2 opacity-90"></i>Items &amp; assets</h1>
        <p class="page-subtitle-landing mb-0">Institution inventory: consumables, equipment, furniture, lab assets, and other property.</p>
    </div>
    <a href="{{ route('inventory-items.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add record</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card card-landing mb-3">
    <div class="card-header-landing py-2"><i class="bi bi-funnel me-2"></i>Filter</div>
    <div class="card-body py-3">
        <form method="GET" action="{{ route('inventory-items.index') }}" class="row g-2 align-items-end flex-wrap">
            <div class="col-md-3">
                <label class="form-label small mb-0">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name, tag, serial, location…">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Kind</label>
                <select name="kind" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(\App\Models\InventoryItem::KINDS as $k => $label)
                        <option value="{{ $k }}" {{ request('kind') === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" {{ request('category') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(\App\Models\InventoryItem::STATUSES as $k => $label)
                        <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ route('inventory-items.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

@php
    $bulkDelete = [
        'bulkModule' => 'inventory',
        'bulkAction' => route('inventory-items.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteInventory',
        'bulkTableId' => 'inventoryItemsTable',
        'bulkItemCount' => $items->count(),
        'bulkHidden' => array_filter(request()->only(['kind', 'status', 'q'])),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Register</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="inventoryItemsTable">
                <thead class="table-light">
                    <tr>
                        @include('partials.bulk-delete.th', $bulkDelete)
                        <th>Asset tag</th>
                        <th>Name</th>
                        <th>Kind</th>
                        <th>Category</th>
                        <th class="text-end">Qty</th>
                        <th>Location</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                    <tr>
                        @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $row->id]))
                        <td class="font-monospace">{{ $row->asset_tag ?? '—' }}</td>
                        <td><strong>{{ $row->name }}</strong></td>
                        <td><span class="badge bg-{{ $row->kind === 'asset' ? 'primary' : 'secondary' }}">{{ $row->kindLabel() }}</span></td>
                        <td>{{ $row->category }}</td>
                        <td class="text-end">{{ number_format($row->quantity) }}@if($row->unit) <span class="text-muted">{{ $row->unit }}</span>@endif</td>
                        <td>{{ $row->location ?? '—' }}</td>
                        <td>{{ $row->conditionLabel() }}</td>
                        <td><span class="badge bg-{{ $row->status === 'active' ? 'success' : ($row->status === 'in_repair' ? 'warning text-dark' : 'secondary') }}">{{ $row->statusLabel() }}</span></td>
                        <td class="text-end text-nowrap">
                            @include('partials.action-edit', ['href' => route('inventory-items.edit', $row), 'class' => 'me-1', 'iconOnly' => true])
                            @canModule('inventory', 'delete')
                            <form action="{{ route('inventory-items.destroy', $row) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Remove this inventory record?'])
                            </form>
                            @endcanModule
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-5">No records yet. <a href="{{ route('inventory-items.create') }}">Add the first item or asset</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($items->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $items->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
