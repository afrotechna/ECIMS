@extends('layouts.app')
@section('title', 'Apply for Staff Leave')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('leave-applications.index') }}">Leave Applications</a>
    <span class="mx-2">/</span>
    <span>Apply</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-calendar-plus me-2 opacity-90"></i>Apply for staff leave</h1>
</div>
<div class="card card-landing">
    <div class="card-body">
        <form action="{{ route('leave-applications.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Staff member</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->staffDisplayName() }}" readonly>
                </div>
                <div class="col-md-6">
                    <label for="from_date" class="form-label">From date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="from_date" name="from_date" value="{{ old('from_date') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="to_date" class="form-label">To date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="to_date" name="to_date" value="{{ old('to_date') }}" required>
                </div>
                <div class="col-12">
                    <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required>{{ old('reason') }}</textarea>
                    <div class="form-text">Allowed leave duration: 14 to 28 days (2 weeks to 1 month).</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit application</button>
                    <a href="{{ route('leave-applications.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
