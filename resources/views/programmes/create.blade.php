@extends('layouts.app')

@section('title', 'Add Programme')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('programmes.index') }}">Programmes</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add Programme</h1>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Programme</div>
    <div class="card-body">
        <form action="{{ route('programmes.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="code" class="form-label">Programme <span class="text-danger">*</span></label>
                    <select class="form-select @error('code') is-invalid @enderror" name="code" id="code" required>
                        <option value="" disabled {{ old('code') ? '' : 'selected' }}>Select programme…</option>
                        @foreach($catalogue as $row)
                            <option
                                value="{{ $row['code'] }}"
                                {{ old('code') === $row['code'] ? 'selected' : '' }}
                            >{{ $row['name'] }} ({{ $row['code'] }})</option>
                        @endforeach
                    </select>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                    <select class="form-select @error('level') is-invalid @enderror" name="level" id="level" required>
                        <option value="" disabled {{ old('level') ? '' : 'selected' }}>Select level…</option>
                        @foreach($levelOptions as $value => $label)
                            <option value="{{ $value }}" {{ (string) old('level') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="duration_years" class="form-label">Duration <span class="text-danger">*</span></label>
                    <select class="form-select @error('duration_years') is-invalid @enderror" name="duration_years" id="duration_years" required>
                        <option value="" disabled {{ old('duration_years') !== null && old('duration_years') !== '' ? '' : 'selected' }}>Select duration…</option>
                        @foreach($durationOptions as $years => $durLabel)
                            <option value="{{ $years }}" {{ (string) old('duration_years') === (string) $years ? 'selected' : '' }}>{{ $durLabel }}</option>
                        @endforeach
                    </select>
                    @error('duration_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Programme</button>
                <a href="{{ route('programmes.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
