{{-- Per-semester gloves / ream (stored in physical_supplies_ack); year = academic year start e.g. 2025 for 2025/2026 --}}
<div class="col-12 mt-2">
    <h6 class="text-muted small text-uppercase fw-bold mb-2">Gloves &amp; ream — {{ $year }}/{{ $year + 1 }}</h6>
    <p class="small text-muted">Select Yes or No for each semester (required items each semester).</p>
</div>
@foreach([\App\Models\Semester::PERIOD_FIRST => 'Semester I', \App\Models\Semester::PERIOD_SECOND => 'Semester II'] as $periodNum => $periodLabel)
    @php
        $slot = $student->physicalSuppliesForSemester((int) $year, (int) $periodNum);
        $gKey = 'supplies.'.$year.'.'.$periodNum.'.gloves';
        $rKey = 'supplies.'.$year.'.'.$periodNum.'.ream';
        $gVal = old($gKey, $slot !== null && array_key_exists('gloves', $slot) ? ($slot['gloves'] ? '1' : '0') : '');
        $rVal = old($rKey, $slot !== null && array_key_exists('ream', $slot) ? ($slot['ream'] ? '1' : '0') : '');
    @endphp
    <div class="col-md-6">
        <div class="border rounded p-3 h-100 bg-light">
            <div class="fw-semibold mb-2">{{ $periodLabel }}</div>
            <div class="mb-2">
                <label class="form-label small mb-0">Clinical gloves</label>
                <select name="supplies[{{ $year }}][{{ $periodNum }}][gloves]" class="form-select form-select-sm">
                    <option value="">— Not set —</option>
                    <option value="1" {{ $gVal === '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ $gVal === '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div>
                <label class="form-label small mb-0">Ream (A4)</label>
                <select name="supplies[{{ $year }}][{{ $periodNum }}][ream]" class="form-select form-select-sm">
                    <option value="">— Not set —</option>
                    <option value="1" {{ $rVal === '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ $rVal === '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
        </div>
    </div>
@endforeach
