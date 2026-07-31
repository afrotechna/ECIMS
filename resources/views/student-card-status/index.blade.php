@extends('layouts.app')
@section('title', 'Student ID & NHIF card status')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Student ID &amp; NHIF card status</span>
</nav>
<div class="page-header-landing mb-3">
    <h1 class="page-title-landing"><i class="bi bi-person-vcard me-2 opacity-90"></i>Student ID &amp; NHIF card status</h1>
    <p class="page-subtitle-landing mb-0">Track card production and notify active students automatically when their card is printed or ready.</p>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('student-card-status.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reg. no or name">
            </div>
            <div class="col-md-3">
                <label class="form-label">Programme</label>
                <select name="programme_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($programmes as $p)
                        <option value="{{ $p->id }}" {{ (string) request('programme_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold"><i class="bi bi-list-check me-1"></i>Students</span>
        <button type="button" id="bulkCardStatusBtn" class="btn btn-sm btn-outline-primary ms-auto d-none" title="Apply to selected" aria-label="Apply to selected">
            <i class="bi bi-pencil-square" aria-hidden="true"></i> Apply to selected
            <span class="badge bg-primary ms-1" id="bulkCardStatusCount">0</span>
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width:2.5rem"><input type="checkbox" id="bulkCardStatusSelectAll" aria-label="Select all"></th>
                    <th>Reg. no</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Student ID card</th>
                    <th>NHIF card</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $byType = $student->cardStatuses->keyBy('document_type');
                @endphp
                <tr>
                    <td class="text-center"><input type="checkbox" class="bulk-card-status-cb" value="{{ $student->id }}" aria-label="Select {{ $student->full_name }}"></td>
                    <td class="fw-medium">{{ $student->reg_no }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->programme->code ?? '—' }}</td>
                    @foreach(\App\Models\StudentCardStatus::DOCUMENT_TYPES as $type => $label)
                    @php $current = $byType->get($type); @endphp
                    <td>
                        <form method="POST" action="{{ route('student-card-status.update', $student) }}" class="d-flex align-items-center gap-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="document_type" value="{{ $type }}">
                            <select name="status" class="form-select form-select-sm" data-no-search onchange="this.form.submit()" style="width:auto">
                                @foreach(\App\Models\StudentCardStatus::STATUSES as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" {{ ($current->status ?? 'pending') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No active students found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if($students->hasPages())<div class="mt-3">{{ $students->links() }}</div>@endif

<form id="bulkCardStatusForm" method="POST" action="{{ route('student-card-status.bulk-update') }}" class="d-none">
    @csrf
    <div id="bulkCardStatusIds"></div>
    <input type="hidden" name="document_type" id="bulkCardStatusDocType">
    <input type="hidden" name="status" id="bulkCardStatusValue">
</form>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('bulkCardStatusSelectAll');
    var btn = document.getElementById('bulkCardStatusBtn');
    var countEl = document.getElementById('bulkCardStatusCount');
    var documentTypes = @json(\App\Models\StudentCardStatus::DOCUMENT_TYPES);
    var statuses = @json(\App\Models\StudentCardStatus::STATUSES);

    function checkedBoxes() {
        return Array.from(document.querySelectorAll('.bulk-card-status-cb:checked'));
    }
    function refresh() {
        var n = checkedBoxes().length;
        countEl.textContent = n;
        btn.disabled = n === 0;
        btn.classList.toggle('d-none', n === 0);
        if (selectAll) {
            var all = document.querySelectorAll('.bulk-card-status-cb');
            selectAll.checked = all.length > 0 && n === all.length;
        }
    }
    document.querySelectorAll('.bulk-card-status-cb').forEach(function (cb) {
        cb.addEventListener('change', refresh);
    });
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.bulk-card-status-cb').forEach(function (cb) { cb.checked = selectAll.checked; });
            refresh();
        });
    }
    if (btn) {
        btn.addEventListener('click', function () {
            var ids = checkedBoxes().map(function (cb) { return cb.value; });
            if (ids.length === 0) return;
            var typeOptions = Object.keys(documentTypes).map(function (key) {
                return '<option value="' + key + '">' + documentTypes[key] + '</option>';
            }).join('');
            var statusOptions = Object.keys(statuses).map(function (key) {
                return '<option value="' + key + '">' + statuses[key] + '</option>';
            }).join('');
            Swal.fire({
                title: 'Update ' + ids.length + ' student(s)',
                html:
                    '<div class="text-start">' +
                    '<label class="form-label small">Document</label>' +
                    '<select id="swalCardDocType" class="form-select mb-2">' + typeOptions + '</select>' +
                    '<label class="form-label small">New status</label>' +
                    '<select id="swalCardStatus" class="form-select">' + statusOptions + '</select>' +
                    '</div>',
                showCancelButton: true,
                confirmButtonText: 'Apply',
                preConfirm: function () {
                    return {
                        documentType: document.getElementById('swalCardDocType').value,
                        status: document.getElementById('swalCardStatus').value,
                    };
                },
            }).then(function (result) {
                if (!result.isConfirmed) return;
                var container = document.getElementById('bulkCardStatusIds');
                container.innerHTML = '';
                ids.forEach(function (id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    container.appendChild(input);
                });
                document.getElementById('bulkCardStatusDocType').value = result.value.documentType;
                document.getElementById('bulkCardStatusValue').value = result.value.status;
                document.getElementById('bulkCardStatusForm').submit();
            });
        });
    }
});
</script>
@endpush
@endsection
