@extends('layouts.app')
@section('title', 'Record certificate collection')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('certificate-collections.index') }}">Certificate collection</a>
    <span class="mx-2">/</span>
    <span>Record</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-patch-check me-2 opacity-90"></i>Record certificate collection</h1>
</div>

<div class="card card-landing" style="max-width: 640px;">
    <div class="card-body">
        @if($students->isEmpty())
            <div class="alert alert-warning mb-0">No eligible students (graduated, no outstanding fees) found.</div>
        @else
        <form action="{{ route('certificate-collections.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                <select class="form-select @error('student_id') is-invalid @enderror" id="student_id" name="student_id" required>
                    <option value="">Select</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}"
                            data-phone="{{ $s->phone }}"
                            {{ (string) old('student_id') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->full_name }} ({{ $s->programme->code ?? '' }})
                        </option>
                    @endforeach
                </select>
                @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="collected_on" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('collected_on') is-invalid @enderror" id="collected_on" name="collected_on" value="{{ old('collected_on', now()->toDateString()) }}" required>
                    @error('collected_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="certificate_number" class="form-label">Certificate number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('certificate_number') is-invalid @enderror" id="certificate_number" name="certificate_number" value="{{ old('certificate_number') }}" required>
                    @error('certificate_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="academic_year" class="form-label">Academic year <span class="text-danger">*</span></label>
                    <select class="form-select @error('academic_year') is-invalid @enderror" id="academic_year" name="academic_year" required>
                        <option value="">Select</option>
                        @foreach(\App\Support\AcademicSession::yearOptions() as $year => $label)
                            <option value="{{ $year }}" {{ (string) old('academic_year') === (string) $year ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="phone_number" class="form-label">Phone number</label>
                    <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number') }}">
                    @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-check mt-3">
                <input class="form-check-input @error('signature_confirmed') is-invalid @enderror" type="checkbox" id="signature_confirmed" name="signature_confirmed" value="1" {{ old('signature_confirmed') ? 'checked' : '' }} required>
                <label class="form-check-label" for="signature_confirmed">Certificate handed over; student's signature obtained on file copy</label>
                @error('signature_confirmed')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('certificate-collections.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
        @endif
    </div>
</div>
@push('scripts')
<script>
document.getElementById('student_id')?.addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    var phone = opt ? opt.getAttribute('data-phone') : '';
    var phoneField = document.getElementById('phone_number');
    if (phoneField && !phoneField.value) phoneField.value = phone || '';
});
</script>
@endpush
@endsection
