@php
    $announcement = $announcement ?? null;
    $selectedLevels = array_map('intval', (array) old('target_nta_levels', $announcement?->target_nta_levels ?? []));
    $selectedProgrammes = array_map('intval', (array) old('target_programme_ids', $announcement?->target_programme_ids ?? []));
    $allProgrammes = old('target_programme_all') !== null
        ? (string) old('target_programme_all') === '1'
        : count($selectedProgrammes) === 0;
    $audience = old('audience', $announcement?->audience ?? 'students');
@endphp

<div class="announce-form-compact">
    <div class="row g-3 align-items-start">
        <div class="col-lg-7">
            <label for="title" class="form-label small mb-1">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm @error('title') is-invalid @enderror" id="title" name="title"
                value="{{ old('title', $announcement?->title) }}" required maxlength="255">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-3 col-md-6">
            <label for="show_until" class="form-label small mb-1">Show until</label>
            <input type="date" class="form-control form-control-sm" id="show_until" name="show_until"
                value="{{ old('show_until', $announcement?->show_until?->format('Y-m-d')) }}">
        </div>
        <div class="col-lg-2 col-md-6">
            <label for="audience" class="form-label small mb-1">Audience <span class="text-danger">*</span></label>
            <select name="audience" id="audience" class="form-select form-select-sm @error('audience') is-invalid @enderror" required>
                @foreach(\App\Models\Announcement::AUDIENCES as $value => $label)
                <option value="{{ $value }}" {{ $audience === $value ? 'selected' : '' }}>{{ $value === 'students' ? 'Students' : ($value === 'staff' ? 'Staff' : 'Everyone') }}</option>
                @endforeach
            </select>
            @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label for="body" class="form-label small mb-1">Message</label>
            <textarea class="form-control form-control-sm @error('body') is-invalid @enderror" id="body" name="body" rows="2"
                placeholder="Details for students or staff…">{{ old('body', $announcement?->body) }}</textarea>
            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row g-3 align-items-end mt-1" id="student-targeting-row">
        <div class="col-12 col-lg-auto" id="student-targeting-panel-levels">
            <label class="form-label small mb-1 d-block">NTA level <span class="text-muted fw-normal">(blank = all)</span></label>
            <div class="d-flex flex-wrap gap-2">
                @foreach([4, 5, 6] as $lvl)
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="checkbox" name="target_nta_levels[]" value="{{ $lvl }}" id="nta_{{ $lvl }}"
                        {{ in_array($lvl, $selectedLevels, true) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="nta_{{ $lvl }}">NTA {{ $lvl }}</label>
                </div>
                @endforeach
            </div>
            @error('target_nta_levels')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-lg" id="student-targeting-panel-prog">
            <label class="form-label small mb-1 d-block">Programme</label>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="checkbox" name="target_programme_all" value="1" id="target_programme_all" {{ $allProgrammes ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="target_programme_all">All</label>
                </div>
                @if(isset($programmes) && $programmes->isNotEmpty())
                <select name="target_programme_ids[]" id="target_programme_ids" class="form-select form-select-sm flex-grow-1" multiple
                    style="min-height:2.25rem; max-height:4.5rem;" {{ $allProgrammes ? 'disabled' : '' }}>
                    @foreach($programmes as $p)
                    <option value="{{ $p->id }}" {{ in_array($p->id, $selectedProgrammes, true) ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            @error('target_programme_ids')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .announce-form-compact .form-label { margin-bottom: .15rem; }
    #student-targeting-row.is-hidden { display: none !important; }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var audience = document.getElementById('audience');
    var row = document.getElementById('student-targeting-row');
    var progAll = document.getElementById('target_programme_all');
    var progSelect = document.getElementById('target_programme_ids');
    function toggleStudentRow() {
        if (row && audience) row.classList.toggle('is-hidden', audience.value === 'staff');
    }
    function toggleProgrammes() {
        if (!progSelect || !progAll) return;
        progSelect.disabled = progAll.checked;
        if (progAll.checked) Array.from(progSelect.options).forEach(function (o) { o.selected = false; });
    }
    if (audience) { audience.addEventListener('change', toggleStudentRow); toggleStudentRow(); }
    if (progAll) { progAll.addEventListener('change', toggleProgrammes); toggleProgrammes(); }
});
</script>
@endpush
@endonce
