@extends('layouts.app')
@section('title', 'Send bulk message')
@section('content')
<nav class="student-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <a href="{{ route('message-logs.index') }}">Message log</a> / <span>Send</span></nav>
<div class="page-header-landing"><h1 class="page-title-landing">Send bulk message</h1></div>

<div class="card card-landing mb-4">
    <div class="card-header-landing"><i class="bi bi-phone me-2"></i>Send test SMS</div>
    <div class="card-body">
        <form action="{{ route('message-logs.test-sms') }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Phone number</label>
                <input type="text" name="test_phone" class="form-control" placeholder="0712345678 or +255712345678" value="{{ old('test_phone') }}" required maxlength="40">
            </div>
            <div class="col-md-3">
                <label class="form-label">Language</label>
                <select name="test_locale" class="form-select">
                    <option value="en" {{ old('test_locale', 'en') === 'en' ? 'selected' : '' }}>English</option>
                    <option value="sw" {{ old('test_locale') === 'sw' ? 'selected' : '' }}>Swahili</option>
                </select>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-send me-1"></i> Send test SMS</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-body">
        <form action="{{ route('message-logs.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Channel</label><select class="form-select" name="channel" required><option value="sms">SMS</option><option value="email">Email</option></select></div>
                <div class="col-12"><label class="form-label">Recipients (comma-separated)</label><textarea class="form-control" name="recipients" rows="3" placeholder="255712345678, 255698765432 or email1@example.com, email2@example.com" required></textarea></div>
                <div class="col-12"><label class="form-label">Subject (for email)</label><input type="text" class="form-control" name="subject"></div>
                <div class="col-12"><label class="form-label">Message body</label><textarea class="form-control" name="body" rows="4" required></textarea></div>
                <div class="col-12"><button type="submit" class="btn btn-primary">Send</button> <a href="{{ route('message-logs.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
            </div>
        </form>
    </div>
</div>
@endsection
