@extends('layouts.app')
@section('title', 'Message log')
@section('content')
<nav class="student-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <span>Message log</span></nav>
<div class="page-header-landing d-flex justify-content-between align-items-center">
<div><h1 class="page-title-landing">Message log</h1></div>
<a href="{{ route('message-logs.create') }}" class="btn btn-primary btn-sm">Send bulk message</a>
</div>
<div class="card card-landing">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Date</th><th>Channel</th><th>Recipient</th><th>Body</th><th>Status</th></tr></thead>
<tbody>
@foreach($logs as $log)
<tr><td>{{ $log->created_at->format('d/m/Y H:i') }}</td><td>{{ $log->channel }}</td><td>{{ $log->recipient }}</td><td>{{ Str::limit($log->body, 50) }}</td><td>{{ $log->status }}</td></tr>
@endforeach
</tbody>
</table>
</div>
</div>
</div>
@if($logs->isEmpty())<p class="p-4 text-muted">No messages.</p>@endif
@if($logs->hasPages())<div class="mt-3">{{ $logs->links() }}</div>@endif
@endsection
