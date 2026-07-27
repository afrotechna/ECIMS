@extends('layouts.app')
@section('title', 'Edit inventory')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('inventory-items.index') }}">Inventory</a>
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-pencil me-2 opacity-90"></i>Edit record</h1>
        <p class="page-subtitle-landing mb-0">{{ $inventory_item->name }}</p>
    </div>
    <a href="{{ route('inventory-items.index') }}" class="btn btn-outline-light btn-sm">Back to list</a>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Details</div>
    <div class="card-body">
        @include('inventory-items._form', [
            'action' => route('inventory-items.update', $inventory_item),
            'method' => 'PUT',
            'submit' => 'Update record',
            'inventory_item' => $inventory_item,
        ])
    </div>
</div>
@endsection
