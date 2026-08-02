@php
    $courses = $grid['courses'];
    $moduleSummary = $grid['module_summary'] ?? [];
@endphp
<div class="mb-3">
    <p class="small text-muted mb-2">
        <i class="bi bi-mortarboard me-1"></i>
        <strong>{{ $grid['programme']->name }} ({{ $grid['programme']->code }})</strong>
        @if($grid['nta_level']) &middot; NTA Level {{ $grid['nta_level'] }} @endif
        &middot; {{ count($grid['rows']) }} student(s)
    </p>

    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0 ca-module-breakdown">
            <thead class="table-light">
                <tr>
                    <th>Module</th>
                    <th class="text-end">Pass</th>
                    <th class="text-end">Fail</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($courses as $course)
                    @php $m = $moduleSummary[$course->id] ?? ['total' => 0, 'pass' => 0, 'fail' => 0]; @endphp
                    <tr>
                        <td>{{ $course->code }}</td>
                        <td class="text-end text-success fw-semibold">{{ $m['pass'] }}</td>
                        <td class="text-end text-danger fw-semibold">{{ $m['fail'] }}</td>
                        <td class="text-end">{{ $m['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered ca-approval-grid mb-0">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" class="align-middle text-center" style="width:2.5rem">#</th>
                    <th rowspan="2" class="align-middle">Student</th>
                    @foreach($courses as $course)
                    <th colspan="{{ $course->has_practical ? 3 : 2 }}" class="text-center">{{ $course->code }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($courses as $course)
                    <th class="text-center small fw-normal">TH COMP</th>
                    @if($course->has_practical)
                    <th class="text-center small fw-normal">{{ strtoupper($course->requires_clinical_rotation ? 'Clinical' : ($course->practicalColumnLabel() ?: 'Practical')) }}</th>
                    @endif
                    <th class="text-center small fw-normal">CA(40%)</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($grid['rows'] as $i => $row)
                <tr>
                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                    <td class="text-nowrap">{{ $row['student']->full_name }}</td>
                    @foreach($courses as $course)
                        @php $cell = $row['cells'][$course->id] ?? null; @endphp
                        @if($cell)
                        <td class="text-end">{{ $cell['theory'] !== null ? number_format((float) $cell['theory'], 1) : '—' }}</td>
                        @if($course->has_practical)
                        <td class="text-end">{{ $cell['practical'] !== null ? number_format((float) $cell['practical'], 1) : '—' }}</td>
                        @endif
                        <td class="text-end fw-semibold ca-remark-cell {{ $cell['cellClass'] }}">{{ $cell['ca'] !== null ? number_format((float) $cell['ca'], 1) : '—' }}</td>
                        @else
                        <td class="text-center text-muted">—</td>
                        @if($course->has_practical)<td class="text-center text-muted">—</td>@endif
                        <td class="text-center text-muted">—</td>
                        @endif
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
