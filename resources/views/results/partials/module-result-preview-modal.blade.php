@php
    $course = $result->course;
    $modalId = 'final-preview-'.($semesterId ?? 0).'-'.$result->id;
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}-label">
                    {{ $course->code ?? 'Module' }} — End-of-semester result
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong>{{ $course->name ?? '—' }}</strong></p>
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">CA (AVCA)</dt>
                    <dd class="col-7">{{ $result->ca_mark !== null ? number_format((float) $result->ca_mark, 1) : '—' }}</dd>
                    <dt class="col-5 text-muted">SE (AVES)</dt>
                    <dd class="col-7">{{ $result->exam_mark !== null ? number_format((float) $result->exam_mark, 1) : '—' }}</dd>
                    <dt class="col-5 text-muted">Final score</dt>
                    <dd class="col-7 fw-semibold">{{ $result->total_mark !== null ? number_format((float) $result->total_mark, 1) : '—' }}</dd>
                    <dt class="col-5 text-muted">Grade</dt>
                    <dd class="col-7 fw-semibold">{{ $result->grade ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Remarks</dt>
                    <dd class="col-7">
                        <span class="badge rounded-0 module-remark-cell d-inline-block px-2 py-1 {{ $result->finalRemarkCellClass() }}">
                            {{ $result->finalDisplayRemark() }}
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">Credits</dt>
                    <dd class="col-7">{{ $course && $course->credits !== null ? number_format((float) $course->credits, 1) : '—' }}</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
