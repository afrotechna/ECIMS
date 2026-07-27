@php
    $course = $result->course;
    $modalId = 'ca-preview-'.($semesterId ?? 0).'-'.$result->id;
    $components = [
        'Test 1' => $result->ca_test1,
        'Test 2' => $result->ca_test2,
        'Assignment 1' => $result->ca_assignment1,
        'Assignment 2' => $result->ca_assignment2,
    ];
    if ($course && $course->has_practical) {
        $label = $course->practicalColumnLabel() ?: 'Practical';
        $components[$label] = $result->ca_practical;
    }
    $hasComponents = collect($components)->contains(fn ($v) => $v !== null && $v !== '');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}-label">
                    {{ $course->code ?? 'Module' }} — CA preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong>{{ $course->name ?? '—' }}</strong></p>
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">CA mark</dt>
                    <dd class="col-7 fw-semibold">{{ $result->ca_mark !== null ? number_format((float) $result->ca_mark, 1) : '—' }}</dd>
                    <dt class="col-5 text-muted">Remarks</dt>
                    <dd class="col-7">
                        <span class="badge rounded-0 ca-remark-cell d-inline-block px-2 py-1 {{ $result->caRemarkCellClass() }}">
                            {{ $result->caDisplayRemark() }}
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">Credits</dt>
                    <dd class="col-7">{{ $course && $course->credits !== null ? number_format((float) $course->credits, 1) : '—' }}</dd>
                </dl>
                @if($hasComponents)
                <hr>
                <p class="small text-muted mb-2">CA components</p>
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                        @foreach($components as $label => $value)
                        @if($value !== null && $value !== '')
                        <tr>
                            <td class="text-muted">{{ $label }}</td>
                            <td class="text-end fw-medium">{{ number_format((float) $value, 1) }}</td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
