@extends('layouts.app')
@section('title', 'Add Department')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('departments.index') }}">Accountancy · Departments</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add Department</h1>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Department details</div>
    <div class="card-body">
        <form action="{{ route('departments.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="code" class="form-label">Code <span class="text-muted">(optional)</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="e.g. SE0010-ADMIN">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Department</button>
                <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
