<div class="card card-landing mb-3 border-0 bg-light">
    <div class="card-body py-2 px-3">
        <button class="btn btn-link btn-sm text-decoration-none text-dark p-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#gradingScaleRef" aria-expanded="false">
            <i class="bi bi-info-circle me-1"></i> Grading system &amp; GPA (click to expand)
        </button>
        <div class="collapse mt-2" id="gradingScaleRef">
            <p class="small text-muted mb-2 mb-md-1">Marks are out of <strong>100%</strong>. GPA = Σ(P×N) ÷ ΣN (max {{ number_format(\App\Support\GradingScale::MAX_GPA, 1) }}).</p>
            <div class="row g-2 align-items-start">
                <div class="col-lg-7">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 bg-white" style="font-size:.75rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Score %</th>
                                    <th>Grade</th>
                                    <th class="text-center">P</th>
                                    <th>Definition</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(\App\Support\GradingScale::BANDS as $band)
                                <tr>
                                    <td>{{ (int) $band['min'] }}–{{ $band['max'] >= 100 ? '100' : (int) floor($band['max']) }}</td>
                                    <td class="fw-semibold">{{ $band['grade'] }}</td>
                                    <td class="text-center">{{ number_format($band['points'], 0) }}</td>
                                    <td>{{ $band['label'] }}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td>—</td>
                                    <td class="fw-semibold">{{ \App\Support\GradingScale::GRADE_INCOMPLETE }}</td>
                                    <td class="text-center">—</td>
                                    <td>Incomplete / Disqualification</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-5 small text-muted">
                    <p class="mb-1"><strong>Award classification</strong> (cumulative GPA):</p>
                    <ul class="mb-0 ps-3">
                        <li>First Class: 3.5 – 4.0</li>
                        <li>Second Class: 3.0 – 3.4</li>
                        <li>Pass: 2.0 – 2.9</li>
                    </ul>
                    <p class="mb-0 mt-2">Award requires all modules at grade <strong>A</strong>, <strong>B</strong>, or <strong>C</strong>.</p>
                </div>
            </div>
        </div>
    </div>
</div>
