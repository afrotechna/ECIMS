@php
    $results = $results ?? collect();
    $semesterId = $semesterId ?? 0;
@endphp
<div class="table-responsive">
    <table class="table table-bordered table-hover mb-0 module-results-table">
        <thead class="table-light">
            <tr>
                <th class="text-center" style="width:3rem">#</th>
                <th>Module code</th>
                <th>Module name</th>
                <th>Module type</th>
                <th class="text-end">Credit</th>
                <th class="text-end">CA</th>
                <th class="text-end">SE</th>
                <th class="text-end">Final score</th>
                <th class="text-center">Grade</th>
                <th class="text-center" title="Grade point">P</th>
                <th class="text-end" title="P × credits">P×N</th>
                <th class="text-center">Remarks</th>
                <th class="text-center" style="width:7rem">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $i => $result)
            @php $course = $result->course; @endphp
            <tr>
                <td class="text-center text-muted">{{ $i + 1 }}</td>
                <td class="fw-medium">{{ $course->code ?? '—' }}</td>
                <td>{{ $course->name ?? '—' }}</td>
                <td>Core</td>
                <td class="text-end">{{ $course && $course->credits !== null ? number_format((float) $course->credits, 1) : '—' }}</td>
                <td class="text-end">{{ $result->ca_mark !== null ? number_format((float) $result->ca_mark, 1) : '—' }}</td>
                <td class="text-end">{{ $result->exam_mark !== null ? number_format((float) $result->exam_mark, 1) : '—' }}</td>
                <td class="text-end">{{ $result->total_mark !== null ? number_format((float) $result->total_mark, 1) : '—' }}</td>
                <td class="text-center fw-medium">{{ $result->grade ?? '—' }}</td>
                <td class="text-center">{{ $result->gradePointValue() !== null ? number_format($result->gradePointValue(), 0) : '—' }}</td>
                <td class="text-end">{{ $result->pointsTimesCredits() !== null ? number_format($result->pointsTimesCredits(), 1) : '—' }}</td>
                <td class="module-remark-cell {{ $result->finalRemarkCellClass() }}">{{ $result->finalDisplayRemark() }}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#final-preview-{{ $semesterId }}-{{ $result->id }}"
                    >
                        <i class="bi bi-journal-text me-1"></i>Preview
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="13" class="text-center text-muted py-4">No modules for this semester.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
