@extends('layouts.app')

@section('title', 'Register Student')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.index') }}">Students</a>
    <span class="mx-2">/</span>
    <span>Register Student</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-person-plus me-2 opacity-90"></i>Register Student</h1>
    <p class="page-subtitle-landing mb-0">System will generate an internal Reg No. NACTVET format: S0000/0000/YYYY or P0000/0000/YYYY.</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Student details</div>
    <div class="card-body">
        <form action="{{ route('students.store') }}" method="POST">
            @csrf
            <div class="form-section-title">Identity &amp; programme</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nactvet_reg_no" class="form-label">NACTVET / Form IV registration no. <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nactvet_reg_no') is-invalid @enderror" id="nactvet_reg_no" name="nactvet_reg_no" value="{{ old('nactvet_reg_no') }}" placeholder="S0001/0001/2026 or P0001/0001/2026" pattern="[SP]\d{4}/\d{4}/\d{4}" title="Format: S0000/0000/2026 or P0000/0000/2026" required>
                    @error('nactvet_reg_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="programme_id" class="form-label">Programme <span class="text-danger">*</span></label>
                    <select class="form-select @error('programme_id') is-invalid @enderror" id="programme_id" name="programme_id" required>
                        <option value="">Select</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ old('programme_id') == $p->id ? 'selected' : '' }}>{{ $p->code }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('programme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="intake_year" class="form-label">Intake (September) <span class="text-danger">*</span></label>
                    <select class="form-select @error('intake_year') is-invalid @enderror" id="intake_year" name="intake_year" required>
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ old('intake_year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('intake_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-section-title">Personal details</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="first_name" class="form-label">First name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="middle_name" class="form-label">Middle name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                </div>
                <div class="col-md-4">
                    <label for="last_name" class="form-label">Last name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="">—</option>
                        <option value="M" {{ old('gender') === 'M' ? 'selected' : '' }}>M</option>
                        <option value="F" {{ old('gender') === 'F' ? 'selected' : '' }}>F</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date_of_birth" class="form-label">Date of birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                </div>
                <div class="col-md-4">
                    <label for="nta_level" class="form-label">NTA level</label>
                    <select class="form-select" id="nta_level" name="nta_level">
                        <option value="">— Select —</option>
                        @foreach(\App\Models\Student::NTA_LEVELS as $val => $label)
                            <option value="{{ $val }}" {{ old('nta_level') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="student_type" class="form-label">Student type</label>
                    <select class="form-select" id="student_type" name="student_type">
                        @foreach(\App\Models\Student::STUDENT_TYPES as $val => $label)
                            <option value="{{ $val }}" {{ old('student_type', 'regular') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12" id="transferFields" style="display:{{ old('student_type') === 'transferred' ? 'block' : 'none' }};">
                    <hr class="my-2">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="transfer_date" class="form-label">Transfer date</label>
                            <input type="date" class="form-control" name="transfer_date" value="{{ old('transfer_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="previous_institution" class="form-label">Previous institution</label>
                            <input type="text" class="form-control" name="previous_institution" value="{{ old('previous_institution') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="previous_programme_id" class="form-label">Previous programme</label>
                            <select class="form-select" name="previous_programme_id">
                                <option value="">— None —</option>
                                @foreach($programmes as $p)
                                    <option value="{{ $p->id }}" {{ old('previous_programme_id') == $p->id ? 'selected' : '' }}>{{ $p->code }} - {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="col-md-4">
                    <label for="academic_standing" class="form-label">Academic standing</label>
                    <select class="form-select" id="academic_standing" name="academic_standing">
                        <option value="">—</option>
                        @foreach(\App\Models\Student::ACADEMIC_STANDINGS as $val => $label)
                            <option value="{{ $val }}" {{ old('academic_standing') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Personal NHIF from home</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="has_personal_nhif" value="1" id="has_personal_nhif_reg" {{ old('has_personal_nhif') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="has_personal_nhif_reg">Has personal NHIF</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="semester_two_fee_band_reg" class="form-label">Semester II tuition band</label>
                    <select class="form-select" id="semester_two_fee_band_reg" name="semester_two_fee_band">
                        <option value="">Auto (transfer / repeat)</option>
                        @foreach(\App\Models\Student::SEMESTER_TWO_FEE_BANDS as $val => $label)
                            <option value="{{ $val }}" {{ old('semester_two_fee_band') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-section-title">Guardian (optional)</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="guardian_name" class="form-label">Guardian name</label>
                    <input type="text" class="form-control" id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}">
                </div>
                <div class="col-md-4">
                    <label for="guardian_phone" class="form-label">Guardian phone</label>
                    <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" value="{{ old('guardian_phone') }}">
                </div>
                <div class="col-md-4">
                    <label for="guardian_relationship_choice" class="form-label">Relationship</label>
                    @include('students.partials.guardian-relationship-field', ['currentValue' => old('guardian_relationship')])
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Register Student</button>
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('student_type').addEventListener('change', function() {
    document.getElementById('transferFields').style.display = this.value === 'transferred' ? 'block' : 'none';
});
</script>
@endpush
@endsection
