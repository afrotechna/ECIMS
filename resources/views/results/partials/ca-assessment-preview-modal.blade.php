@php
    $course = $result->course;
    $modalId = 'ca-preview-'.($semesterId ?? 0).'-'.$result->id;
    $theoryPass = (float) config('college.ca_theory_pass_mark', 10.1);
    $practicalPass = (float) config('college.ca_practical_pass_mark', 50);

    $components = [];
    if ($result->ca_theory !== null && $result->ca_theory !== '') {
        $components['Theory average'] = [
            'value' => $result->ca_theory,
            'pass' => (float) $result->ca_theory > $theoryPass,
            'threshold' => $theoryPass,
        ];
    }
    if ($course && $course->has_practical && $result->ca_practical !== null && $result->ca_practical !== '') {
        $label = $course->practicalColumnLabel() ?: 'Practical';
        $components[$label.' average'] = [
            'value' => $result->ca_practical,
            'pass' => (float) $result->ca_practical > $practicalPass,
            'threshold' => $practicalPass,
        ];
    }
    foreach ([
        'Test 1' => $result->ca_test1,
        'Test 2' => $result->ca_test2,
        'Assignment 1' => $result->ca_assignment1,
        'Assignment 2' => $result->ca_assignment2,
    ] as $label => $value) {
        if ($value !== null && $value !== '') {
            $components[$label] = ['value' => $value, 'pass' => null, 'threshold' => null];
        }
    }
    $hasComponents = count($components) > 0;
    $failureReasons = $result->caFailureComponents();
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
                        @foreach($components as $label => $c)
                        <tr>
                            <td class="text-muted">{{ $label }}</td>
                            <td class="text-end fw-medium">
                                {{ number_format((float) $c['value'], 1) }}
                                @if($c['pass'] !== null)
                                <span class="badge rounded-0 ms-1 {{ $c['pass'] ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ $c['pass'] ? 'Pass' : 'Below '.number_format($c['threshold'], 1) }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
                @if(! empty($failureReasons))
                <div class="alert alert-danger small mt-3 mb-0">
                    <strong>Failed:</strong> {{ implode(', ', $failureReasons) }} — not eligible for the end-of-semester exam in this module.
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
