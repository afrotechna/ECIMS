@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Course> $modules */
    /** @var float $creditsTotal */
    $modules = $modules ?? collect();
    $creditsTotal = $creditsTotal ?? 0;
@endphp
@if($modules->isEmpty())
    <p class="text-muted mb-0 py-3 px-3">No modules listed for this semester yet.</p>
@else
    <div class="table-responsive rounded border">
        <table class="table table-hover align-middle mb-0 table-sm">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Module code</th>
                    <th>Module name</th>
                    <th>CA %</th>
                    <th>SE %</th>
                    <th>Credits</th>
                </tr>
            </thead>
            <tbody>
                @foreach($modules as $i => $module)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $module->code }}</strong></td>
                    <td>{{ $module->name }}</td>
                    <td>{{ $module->ca_weight }}</td>
                    <td>{{ $module->exam_weight }}</td>
                    <td>{{ $module->credits }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="5" class="fw-semibold border-top">Total credits</td>
                    <td class="fw-semibold border-top">{{ number_format($creditsTotal, 2, '.', '') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
