{{--
    Registration payment: tuition category + NHIF / NACTVET lines (same rules as RecordPaymentService).
--}}
@php
    $paymentTuitionDefault = $paymentTuitionDefault ?? 'continue';
    $chargesNhifQa = $chargesNhifQa ?? true;
    $oldSlots = [
        'slot_sem1_nhif' => (int) old('slot_sem1_nhif', 0),
        'slot_sem1_nactvet_qa' => (int) old('slot_sem1_nactvet_qa', 0),
    ];
@endphp
<input type="hidden" name="academic_year" value="{{ $paymentAcademicYear }}">

@if(!($feeStructureResolved ?? false))
    <div class="alert alert-danger mb-0">
        No active fee schedule for session <strong>{{ $paymentAcademicYear }}/{{ $paymentAcademicYear + 1 }}</strong>.
        Add one under <strong>Finance &rarr; Fees</strong> for this session and programme (or <em>All programmes</em>).
    </div>
@else
    <p class="small text-muted mb-3">
        Session <strong>{{ $paymentAcademicYear }}/{{ $paymentAcademicYear + 1 }}</strong>.
    </p>

    <div class="card border mb-3">
        <div class="card-header py-2 fw-semibold small text-uppercase">Semester One &mdash; bank fees</div>
        <div class="card-body">
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="rw_tuition_category">Tuition fee</label>
            <select name="tuition_category" id="rw_tuition_category" class="form-select @error('tuition_category') is-invalid @enderror" required>
                <option value="new_student" {{ old('tuition_category', $paymentTuitionDefault) === 'new_student' ? 'selected' : '' }}>New student &mdash; Semester I tuition only</option>
                <option value="continue" {{ old('tuition_category', $paymentTuitionDefault) === 'continue' ? 'selected' : '' }}>Continuing &mdash; Semester I + II (continuous rate)</option>
                <option value="repeat" {{ old('tuition_category', $paymentTuitionDefault) === 'repeat' ? 'selected' : '' }}>Repeating &mdash; Semester I + II (repeat rate)</option>
                <option value="transfer" {{ old('tuition_category', $paymentTuitionDefault) === 'transfer' ? 'selected' : '' }}>Transferred &mdash; Semester I + II (transfer / repeat rate)</option>
            </select>
            @error('tuition_category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        @if($chargesNhifQa)
        <div class="col-md-6 col-lg-4">
            <label class="form-label" for="rw_slot_sem1_nhif">NHIF (Semester I)</label>
            <select name="slot_sem1_nhif" id="rw_slot_sem1_nhif" class="form-select fee-slot-select rw-fee-slot" required></select>
            @error('slot_sem1_nhif')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 col-lg-4">
            <label class="form-label" for="rw_slot_sem1_nactvet_qa">NACTVET QA (Semester I)</label>
            <select name="slot_sem1_nactvet_qa" id="rw_slot_sem1_nactvet_qa" class="form-select fee-slot-select rw-fee-slot" required></select>
            @error('slot_sem1_nactvet_qa')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        @else
        <input type="hidden" name="slot_sem1_nhif" value="0">
        <input type="hidden" name="slot_sem1_nactvet_qa" value="0">
        <div class="col-12">
            <div class="alert alert-light border py-2 mb-0 small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                NHIF and NACTVET QA are already covered for this academic year — not charged again this semester.
            </div>
        </div>
        @endif
        <div class="col-12">
            <div class="alert alert-light border py-2 mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Receipt total</span>
                <span class="fs-5" id="rw_fee_slot_total">0 TZS</span>
            </div>
        </div>
    </div>
        </div>
    </div>

    <hr class="my-4">
    <p class="small fw-semibold text-uppercase text-muted mb-2">Control numbers (per fee line)</p>
    <div class="row g-3 mb-2">
        <div class="col-md-4" id="rw_ref_tuition_wrap">
            <label for="rw_reference_tuition" class="form-label">Tuition control no.</label>
            <input type="text" class="form-control @error('reference_tuition') is-invalid @enderror" id="rw_reference_tuition" name="reference_tuition" value="{{ old('reference_tuition') }}" maxlength="100" autocomplete="off" placeholder="Bank / GePG control number">
            @error('reference_tuition')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @if($chargesNhifQa)
        <div class="col-md-4" id="rw_ref_nhif_wrap">
            <label for="rw_reference_nhif" class="form-label">NHIF control no.</label>
            <input type="text" class="form-control @error('reference_nhif') is-invalid @enderror" id="rw_reference_nhif" name="reference_nhif" value="{{ old('reference_nhif') }}" maxlength="100" autocomplete="off" placeholder="NHIF payment reference">
            @error('reference_nhif')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" id="rw_ref_nactvet_wrap">
            <label for="rw_reference_nactvet_qa" class="form-label">NACTVET QA control no.</label>
            <input type="text" class="form-control @error('reference_nactvet_qa') is-invalid @enderror" id="rw_reference_nactvet_qa" name="reference_nactvet_qa" value="{{ old('reference_nactvet_qa') }}" maxlength="100" autocomplete="off" placeholder="NACTVET QA reference">
            @error('reference_nactvet_qa')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @endif
    </div>
    <div class="row g-3">
        <div class="col-md-3">
            <label for="rw_payment_method" class="form-label">Payment method <span class="text-danger">*</span></label>
            <select class="form-select" id="rw_payment_method" name="payment_method" required>
                @foreach($paymentMethods as $value => $label)
                    <option value="{{ $value }}" {{ old('payment_method', 'cash') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="rw_paid_at" class="form-label">Date &amp; time <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control @error('paid_at') is-invalid @enderror" id="rw_paid_at" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required>
            @error('paid_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label for="rw_notes" class="form-label">Notes</label>
            <input type="text" class="form-control" id="rw_notes" name="notes" value="{{ old('notes') }}" maxlength="500">
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        var feeSlotsByKey = @json($feeSlotsByKey);
        var academicYear = @json((string) $paymentAcademicYear);
        var studentProgrammeId = @json($student->programme_id);
        var oldSlots = @json($oldSlots);
        var submitBtn = document.getElementById('regWizardContinueBtn');
        var totalEl = document.getElementById('rw_fee_slot_total');
        var tuitionSel = document.getElementById('rw_tuition_category');

        var slotIds = ['rw_slot_sem1_nhif', 'rw_slot_sem1_nactvet_qa'];

        function programmeKey(pid) {
            return pid != null && pid !== '' ? String(pid) : 'all';
        }

        function fmt(n) {
            return Math.round(Number(n) || 0).toLocaleString() + ' TZS';
        }

        function buildOptions(scheduled) {
            scheduled = Number(scheduled) || 0;
            var opts = '';
            if (scheduled <= 0) {
                opts += '<option value="0" selected>Not applicable (0)</option>';
                return opts;
            }
            opts += '<option value="0">Not paying this line</option>';
            opts += '<option value="' + scheduled + '">Pay scheduled &mdash; ' + fmt(scheduled) + '</option>';
            return opts;
        }

        function getSlotsFromSchedule() {
            var pk = programmeKey(studentProgrammeId) + '_' + academicYear;
            var slots = feeSlotsByKey[pk];
            if (!slots || !Object.keys(slots).length) {
                pk = 'all_' + academicYear;
                slots = feeSlotsByKey[pk];
            }
            return slots || null;
        }

        function tuitionFromCategory(slots, category) {
            if (!slots) return 0;
            var s1 = parseInt(slots.sem1_tuition, 10) || 0;
            var s2c = parseInt(slots.sem2_continuous, 10) || 0;
            var s2r = parseInt(slots.sem2_repeat, 10) || 0;
            switch (category) {
                case 'new_student': return s1;
                case 'continue': return s1 + s2c;
                case 'repeat':
                case 'transfer': return s1 + s2r;
                default: return 0;
            }
        }

        function refreshFeeSlots() {
            var slots = getSlotsFromSchedule();
            slotIds.forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                if (!slots) {
                    el.innerHTML = '<option value="">&mdash;</option>';
                    el.disabled = true;
                    return;
                }
                var key = id.indexOf('nhif') !== -1 ? 'sem1_nhif' : 'sem1_nactvet_qa';
                var scheduled = slots[key];
                el.disabled = false;
                el.innerHTML = buildOptions(scheduled);
                var rawId = id.replace('rw_', '');
                var pick = oldSlots[rawId] != null ? Number(oldSlots[rawId]) : 0;
                if (scheduled <= 0) {
                    el.value = '0';
                } else if (pick === 0 || pick === scheduled) {
                    el.value = String(pick);
                } else {
                    el.value = '0';
                }
            });
            updateTotal();
        }

        function updateTotal() {
            var slots = getSlotsFromSchedule();
            var cat = tuitionSel ? tuitionSel.value : 'continue';
            var tuition = tuitionFromCategory(slots, cat);
            var sum = tuition;
            slotIds.forEach(function (id) {
                var el = document.getElementById(id);
                if (el && !el.disabled) {
                    sum += parseInt(el.value, 10) || 0;
                }
            });
            if (totalEl) totalEl.textContent = fmt(sum);
            var okSchedule = !!slots;
            var okTotal = sum > 0;
            if (submitBtn) submitBtn.disabled = !(okSchedule && okTotal);
        }

        if (tuitionSel) tuitionSel.addEventListener('change', syncControlFields);
        slotIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', syncControlFields);
        });

        function syncControlFields() {
            updateTotal();
            var slots = getSlotsFromSchedule();
            var cat = tuitionSel ? tuitionSel.value : 'continue';
            var tuition = tuitionFromCategory(slots, cat);
            var nhif = 0;
            var qa = 0;
            var nhifEl = document.getElementById('rw_slot_sem1_nhif');
            var qaEl = document.getElementById('rw_slot_sem1_nactvet_qa');
            if (nhifEl && !nhifEl.disabled) nhif = parseInt(nhifEl.value, 10) || 0;
            if (qaEl && !qaEl.disabled) qa = parseInt(qaEl.value, 10) || 0;

            setRefField('rw_ref_tuition_wrap', 'rw_reference_tuition', tuition > 0);
            setRefField('rw_ref_nhif_wrap', 'rw_reference_nhif', nhif > 0);
            setRefField('rw_ref_nactvet_wrap', 'rw_reference_nactvet_qa', qa > 0);
        }

        function setRefField(wrapId, inputId, required) {
            var wrap = document.getElementById(wrapId);
            var input = document.getElementById(inputId);
            if (!wrap || !input) return;
            input.required = required;
            wrap.style.opacity = required ? '1' : '0.55';
        }

        refreshFeeSlots();
        syncControlFields();
    })();
    </script>
    @endpush
@endif

