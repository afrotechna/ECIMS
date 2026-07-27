@extends('layouts.app')
@section('title', 'Add inventory')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('inventory-items.index') }}">Inventory</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add item or asset</h1>
        <p class="page-subtitle-landing mb-0">Record supplies (quantity) or fixed assets (tag / serial).</p>
    </div>
    <a href="{{ route('inventory-items.index') }}" class="btn btn-outline-light btn-sm">Back to list</a>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Details</div>
    <div class="card-body">
        @include('inventory-items._form', ['action' => route('inventory-items.store'), 'method' => 'POST', 'submit' => 'Save record'])
    </div>
</div>
@endsection
