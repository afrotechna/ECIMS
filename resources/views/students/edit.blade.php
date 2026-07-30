@extends('layouts.app')

@section('title', 'Edit Student')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.index') }}">Students</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a>
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit Student</h1>
    <p class="page-subtitle-landing mb-0">System Reg No: <strong>{{ $student->reg_no }}</strong> (cannot be changed)</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil me-2"></i>Student details</div>
    <div class="card-body">
        <form action="{{ route('students.update', $student) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nactvet_reg_no" class="form-label">NACTVET / Form IV registration no. <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nactvet_reg_no') is-invalid @enderror" id="nactvet_reg_no" name="nactvet_reg_no" value="{{ old('nactvet_reg_no', $student->nactvet_reg_no) }}" placeholder="S0001/0001/2026 or P0001/0001/2026" pattern="[SP]\d{4}/\d{4}/\d{4}" title="Format: S0000/0000/2026 or P0000/0000/2026" required>
                    @error('nactvet_reg_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="programme_id" class="form-label">Programme <span class="text-danger">*</span></label>
                    <select class="form-select" id="programme_id" name="programme_id" required>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ old('programme_id', $student->programme_id) == $p->id ? 'selected' : '' }}>{{ $p->code }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="intake_year" class="form-label">Intake year <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="intake_year" name="intake_year" value="{{ old('intake_year', $student->intake_year) }}" min="2020" max="2030" required>
                </div>
                <div class="col-md-4">
                    <label for="first_name" class="form-label">First name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $student->first_name) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="middle_name" class="form-label">Middle name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}">
                </div>
                <div class="col-md-4">
                    <label for="last_name" class="form-label">Last name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $student->last_name) }}" required>
                </div>
                <div class="col-md-2">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="">—</option>
                        <option value="M" {{ old('gender', $student->gender) === 'M' ? 'selected' : '' }}>M</option>
                        <option value="F" {{ old('gender', $student->gender) === 'F' ? 'selected' : '' }}>F</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date_of_birth" class="form-label">Date of birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label for="nta_level" class="form-label">NTA level</label>
                    <select class="form-select" id="nta_level" name="nta_level">
                        <option value="">— Select —</option>
                        @foreach(\App\Models\Student::NTA_LEVELS as $val => $label)
                            <option value="{{ $val }}" {{ old('nta_level', $student->nta_level) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="student_type" class="form-label">Student type</label>
                    <select class="form-select" id="student_type" name="student_type">
                        @foreach(\App\Models\Student::STUDENT_TYPES as $val => $label)
                            <option value="{{ $val }}" {{ old('student_type', $student->student_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12" id="transferFields" style="display:{{ old('student_type', $student->student_type) === 'transferred' ? 'block' : 'none' }};">
                    <hr class="my-2">
                    <h6 class="text-muted">Transfer details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="transfer_date" class="form-label">Transfer date</label>
                            <input type="date" class="form-control" id="transfer_date" name="transfer_date" value="{{ old('transfer_date', $student->transfer_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="previous_institution" class="form-label">Previous institution</label>
                            <input type="text" class="form-control" id="previous_institution" name="previous_institution" value="{{ old('previous_institution', $student->previous_institution) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="previous_programme_id" class="form-label">Previous programme</label>
                            <select class="form-select" id="previous_programme_id" name="previous_programme_id">
                                <option value="">— None —</option>
                                @foreach($programmes as $p)
                                    <option value="{{ $p->id }}" {{ old('previous_programme_id', $student->previous_programme_id) == $p->id ? 'selected' : '' }}>{{ $p->code }} - {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $student->email) }}">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $student->phone) }}">
                </div>
                <div class="col-md-4">
                    <label for="biometric_id" class="form-label">Biometric ID</label>
                    <input type="text" class="form-control" id="biometric_id" name="biometric_id" value="{{ old('biometric_id', $student->biometric_id) }}" placeholder="ZKTeco device User ID">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" {{ old('status', $student->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="deactivated" {{ old('status', $student->status) === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                        <option value="withdrawn" {{ old('status', $student->status) === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                        <option value="graduated" {{ old('status', $student->status) === 'graduated' ? 'selected' : '' }}>Graduated</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="academic_standing" class="form-label">Academic standing</label>
                    <select class="form-select" id="academic_standing" name="academic_standing">
                        <option value="">—</option>
                        @foreach(\App\Models\Student::ACADEMIC_STANDINGS as $val => $label)
                            <option value="{{ $val }}" {{ old('academic_standing', $student->academic_standing) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <hr class="my-3">
            <h6 class="text-muted mb-2"><i class="bi bi-person-badge me-1"></i>Guardian (optional)</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="guardian_name" class="form-label">Guardian name</label>
                    <input type="text" class="form-control" id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}">
                </div>
                <div class="col-md-4">
                    <label for="guardian_phone" class="form-label">Guardian phone</label>
                    <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone) }}">
                </div>
                <div class="col-md-4">
                    <label for="guardian_relationship" class="form-label">Relationship</label>
                    <input type="text" class="form-control" id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship', $student->guardian_relationship) }}" placeholder="e.g. Parent, Guardian">
                </div>
            </div>

            <hr class="my-4">
            <h6 class="text-muted mb-3"><i class="bi bi-clipboard2-data me-1"></i>Admission control sheet (optional)</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="tuition_status_override" class="form-label">Tuition fee status (override)</label>
                    <select class="form-select" id="tuition_status_override" name="tuition_status_override">
                        <option value="">Auto (compare fee schedule vs tuition payments)</option>
                        @foreach(\App\Models\Student::TUITION_STATUS_OVERRIDES as $val => $label)
                            <option value="{{ $val }}" {{ old('tuition_status_override', $student->tuition_status_override) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Personal NHIF from home</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="has_personal_nhif" value="1" id="has_personal_nhif" {{ old('has_personal_nhif', $student->has_personal_nhif) ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_personal_nhif">Has personal NHIF — do not charge college NHIF</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="semester_two_fee_band" class="form-label">Semester II tuition band</label>
                    <select class="form-select" id="semester_two_fee_band" name="semester_two_fee_band">
                        <option value="">Auto (from transfer / repeat year)</option>
                        @foreach(\App\Models\Student::SEMESTER_TWO_FEE_BANDS as $val => $label)
                            <option value="{{ $val }}" {{ old('semester_two_fee_band', $student->semester_two_fee_band) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="class_group" class="form-label">Class</label>
                    <input type="text" class="form-control" id="class_group" name="class_group" value="{{ old('class_group', $student->class_group) }}" maxlength="80" placeholder="e.g. CMT-L4-A">
                </div>
                <div class="col-md-3">
                    <label for="reporting_status" class="form-label">Reporting status</label>
                    <select class="form-select" id="reporting_status" name="reporting_status">
                        <option value="">—</option>
                        @foreach(\App\Models\Student::REPORTING_STATUSES as $val => $label)
                            <option value="{{ $val }}" {{ old('reporting_status', $student->reporting_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="reporting_date" class="form-label">Reporting date</label>
                    <input type="date" class="form-control" id="reporting_date" name="reporting_date" value="{{ old('reporting_date', $student->reporting_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label for="tuition_completion_pledge_date" class="form-label">Tuition completion pledge date</label>
                    <input type="date" class="form-control" id="tuition_completion_pledge_date" name="tuition_completion_pledge_date" value="{{ old('tuition_completion_pledge_date', $student->tuition_completion_pledge_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label for="nhif_status" class="form-label">NHIF status</label>
                    <select class="form-select" id="nhif_status" name="nhif_status">
                        <option value="">—</option>
                        @foreach(\App\Models\Student::FEE_STATUSES as $val => $label)
                            <option value="{{ $val }}" {{ old('nhif_status', $student->nhif_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tuition_payment_ref" class="form-label">Tuition control no.</label>
                    <input type="text" class="form-control" id="tuition_payment_ref" name="tuition_payment_ref" value="{{ old('tuition_payment_ref', $student->tuition_payment_ref) }}">
                </div>
                <div class="col-md-3">
                    <label for="nhif_payment_ref" class="form-label">NHIF control no.</label>
                    <input type="text" class="form-control" id="nhif_payment_ref" name="nhif_payment_ref" value="{{ old('nhif_payment_ref', $student->nhif_payment_ref) }}">
                </div>
                <div class="col-md-3">
                    <label for="nactvet_qa_status" class="form-label">NACTVET QA status</label>
                    <select class="form-select" id="nactvet_qa_status" name="nactvet_qa_status">
                        <option value="">—</option>
                        @foreach(\App\Models\Student::FEE_STATUSES as $val => $label)
                            <option value="{{ $val }}" {{ old('nactvet_qa_status', $student->nactvet_qa_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="nactvet_qa_payment_ref" class="form-label">NACTVET QA payment ref</label>
                    <input type="text" class="form-control" id="nactvet_qa_payment_ref" name="nactvet_qa_payment_ref" value="{{ old('nactvet_qa_payment_ref', $student->nactvet_qa_payment_ref) }}">
                </div>
                <div class="col-md-3">
                    <label for="joining_instructions_submitted" class="form-label">Joining instructions submitted</label>
                    <select class="form-select" id="joining_instructions_submitted" name="joining_instructions_submitted">
                        <option value="">—</option>
                        <option value="Yes" {{ old('joining_instructions_submitted', $student->joining_instructions_submitted) === 'Yes' ? 'selected' : '' }}>Yes</option>
                        <option value="No" {{ old('joining_instructions_submitted', $student->joining_instructions_submitted) === 'No' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                @include('students.partials.physical-supplies-fields', ['student' => $student, 'year' => $suppliesYear ?? \App\Support\AcademicSession::currentStartYear()])
                @include('students.partials.certificates-submitted-fields', ['student' => $student])
                <div class="col-md-3">
                    <label for="class_property_received" class="form-label">Class property received</label>
                    <select class="form-select" id="class_property_received" name="class_property_received">
                        <option value="">—</option>
                        <option value="Yes" {{ old('class_property_received', $student->class_property_received) === 'Yes' ? 'selected' : '' }}>Yes</option>
                        <option value="No" {{ old('class_property_received', $student->class_property_received) === 'No' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="chair_number" class="form-label">Chair number</label>
                    <input type="text" class="form-control" id="chair_number" name="chair_number" value="{{ old('chair_number', $student->chair_number) }}">
                </div>
                <div class="col-md-3">
                    <label for="table_number" class="form-label">Table number</label>
                    <input type="text" class="form-control" id="table_number" name="table_number" value="{{ old('table_number', $student->table_number) }}">
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Student</button>
                <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary">Cancel</a>
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
