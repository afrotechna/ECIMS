@extends('layouts.app')
@section('title', 'New clinical rotation')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('clinical-rotations.index') }}">Clinical rotation</a>
    <span class="mx-2">/</span>
    <span>New</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-circle me-2 opacity-90"></i>New rotation round</h1>
</div>
<div class="card card-landing">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('clinical-rotations.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Semester II <span class="text-danger">*</span></label>
                <select name="semester_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($semesters as $s)
                        <option value="{{ $s->id }}" {{ (string) old('semester_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
                @if($semesters->isEmpty())
                    <p class="small text-danger mt-1 mb-0">No active Semester II records. Add one under Semesters first.</p>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Programme <span class="text-danger">*</span></label>
                <select name="programme_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($programmes as $p)
                        <option value="{{ $p->id }}" {{ (string) old('programme_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">NTA level <span class="text-danger">*</span></label>
                <select name="nta_level" class="form-select" required>
                    <option value="4" {{ (string) old('nta_level') === '4' ? 'selected' : '' }}>NTA Level 4 (first year)</option>
                    <option value="5" {{ (string) old('nta_level', '5') === '5' ? 'selected' : '' }}>NTA Level 5 (second year)</option>
                    <option value="6" {{ (string) old('nta_level') === '6' ? 'selected' : '' }}>NTA Level 6 (third year)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Rotation week — Monday <span class="text-danger">*</span></label>
                <input type="date" name="rotation_week_monday" class="form-control" value="{{ old('rotation_week_monday') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Rotation week — Friday <span class="text-danger">*</span></label>
                <input type="date" name="rotation_week_friday" class="form-control" value="{{ old('rotation_week_friday') }}" required>
                <p class="small text-muted mb-0 mt-1">Must be the Friday of the same week as the Monday above.</p>
            </div>
            <div class="col-md-6">
                <label class="form-label">Weeks in each department (schedule grid) <span class="text-danger">*</span></label>
                @php
                    $oldNta = (string) old('nta_level', '5');
                    $defWeeks = (string) old('schedule_weeks_per_block', $oldNta === '4' ? '1' : '2');
                @endphp
                <select name="schedule_weeks_per_block" class="form-select" required>
                    <option value="1" {{ $defWeeks === '1' ? 'selected' : '' }}>1 week per department (typical NTA 4)</option>
                    <option value="2" {{ $defWeeks === '2' ? 'selected' : '' }}>2 weeks per department — fortnight (typical NTA 5–6)</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Title (optional)</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" maxlength="200" placeholder="e.g. Sem II 2025/26 clinical posting">
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary" @if($semesters->isEmpty()) disabled @endif><i class="bi bi-check2-circle me-1"></i>Save and allocate</button>
                <a href="{{ route('clinical-rotations.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var nta = document.querySelector('select[name="nta_level"]');
    var weeks = document.querySelector('select[name="schedule_weeks_per_block"]');
    if (!nta || !weeks) return;
    nta.addEventListener('change', function () {
        weeks.value = this.value === '4' ? '1' : '2';
    });
})();
</script>
@endpush
@endsection
