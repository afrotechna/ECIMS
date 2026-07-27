@php
    $results = $results ?? collect();
    $semesterId = $semesterId ?? 0;
@endphp
<div class="table-responsive">
    <table class="table table-bordered table-hover mb-0 ca-assessments-table">
        <thead class="table-light">
            <tr>
                <th class="text-center" style="width:3rem">#</th>
                <th>Module code</th>
                <th>Module name</th>
                <th>Module type</th>
                <th class="text-end">Credit</th>
                <th class="text-end">CA</th>
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
                <td class="ca-remark-cell {{ $result->caRemarkCellClass() }}">{{ $result->caDisplayRemark() }}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#ca-preview-{{ $semesterId }}-{{ $result->id }}"
                    >
                        <i class="bi bi-journal-text me-1"></i>Preview
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">No modules for this semester.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
