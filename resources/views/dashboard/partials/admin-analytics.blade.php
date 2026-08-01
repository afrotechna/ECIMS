@php
    $levelLabels = array_map(fn ($n) => __('ui.charts.level_n', ['n' => $n]), [4, 5, 6]);
    $levelData = [];
    foreach ([4, 5, 6] as $lvl) {
        $levelData[] = (int) ($chartByNtaLevel[$lvl] ?? 0);
    }
    $levelTotal = array_sum($levelData);

    $progLabels = array_keys($chartByProgramme ?? []);
    $progData = array_values($chartByProgramme ?? []);
    $progTotal = array_sum($progData);

    $cmtLevels = $chartByProgrammeLevel['CMT'] ?? [4 => 0, 5 => 0, 6 => 0];
    $mltLevels = $chartByProgrammeLevel['MLT'] ?? [4 => 0, 5 => 0, 6 => 0];
    $cmtData = [(int) ($cmtLevels[4] ?? 0), (int) ($cmtLevels[5] ?? 0), (int) ($cmtLevels[6] ?? 0)];
    $mltData = [(int) ($mltLevels[4] ?? 0), (int) ($mltLevels[5] ?? 0), (int) ($mltLevels[6] ?? 0)];

    $levelColors = ['rgba(29, 157, 87, 0.9)', 'rgba(26, 79, 181, 0.9)', 'rgba(56, 182, 232, 0.9)'];
    $cmtColor = 'rgba(29, 157, 87, 0.88)';
    $mltColor = 'rgba(56, 182, 232, 0.88)';
    $progColors = ['rgba(29, 157, 87, 0.88)', 'rgba(56, 182, 232, 0.88)'];
    $chartStudentsLabel = __('ui.charts.students');
@endphp

<div class="dashboard-analytics mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="dashboard-analytics__title h6 mb-0 text-uppercase fw-bold text-muted" style="letter-spacing:.06em">{{ __('ui.charts.student_analytics') }}</h2>
        <span class="small text-muted">{{ __('ui.charts.on_register_hint', ['count' => number_format($activeStudentCount ?? 0)]) }}</span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-4">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-bar-chart-fill text-primary"></i>
                    <span>{{ __('ui.charts.by_nta_level') }}</span>
                </div>
                <div class="dashboard-chart-card__body" style="height:220px">
                    <canvas id="chartBarNtaLevel" aria-label="{{ __('ui.charts.by_nta_level') }}"></canvas>
                </div>
                <div class="dashboard-chart-card__foot">
                    @foreach ([4, 5, 6] as $i => $lvl)
                        <span>{{ __('ui.charts.level_short', ['n' => $lvl]) }}: <strong>{{ number_format($levelData[$i]) }}</strong></span>
                    @endforeach
                    <span class="text-muted">· {{ __('ui.charts.total') }} {{ number_format($levelTotal) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-pie-chart-fill text-success"></i>
                    <span>{{ __('ui.charts.by_programme') }}</span>
                </div>
                <div class="dashboard-chart-card__body" style="height:220px">
                    <canvas id="chartPieProgramme" aria-label="{{ __('ui.charts.by_programme') }}"></canvas>
                </div>
                <div class="dashboard-chart-card__foot">
                    @foreach ($progLabels as $i => $code)
                        <span>{{ $code }}: <strong>{{ number_format($progData[$i] ?? 0) }}</strong></span>
                    @endforeach
                    <span class="text-muted">· {{ __('ui.charts.total') }} {{ number_format($progTotal) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-heart-pulse text-success"></i>
                    <span>{{ __('ui.charts.cmt_by_level') }}</span>
                </div>
                <div class="dashboard-chart-card__body" style="height:220px">
                    <canvas id="chartBarCmtLevel" aria-label="{{ __('ui.charts.cmt_by_level') }}"></canvas>
                </div>
                <div class="dashboard-chart-card__foot">
                    @foreach ($levelLabels as $i => $lbl)
                        <span>{{ $lbl }}: <strong>{{ number_format($cmtData[$i]) }}</strong></span>
                    @endforeach
                    <span class="text-muted">· {{ number_format(array_sum($cmtData)) }} CMT</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-droplet-half text-primary"></i>
                    <span>{{ __('ui.charts.mlt_by_level') }}</span>
                </div>
                <div class="dashboard-chart-card__body" style="height:220px">
                    <canvas id="chartBarMltLevel" aria-label="{{ __('ui.charts.mlt_by_level') }}"></canvas>
                </div>
                <div class="dashboard-chart-card__foot">
                    @foreach ($levelLabels as $i => $lbl)
                        <span>{{ $lbl }}: <strong>{{ number_format($mltData[$i]) }}</strong></span>
                    @endforeach
                    <span class="text-muted">· {{ number_format(array_sum($mltData)) }} MLT</span>
                </div>
            </div>
        </div>
    </div>

    @php
        $hasPayments = auth()->user()->canModule('finance_payments', 'view');
        $hasAcademics = auth()->user()->canAccessAcademics();
        $trendCol = ($hasPayments && $hasAcademics) ? 'col-6 col-xl-3' : (($hasPayments || $hasAcademics) ? 'col-md-6 col-lg-4' : 'col-12');
    @endphp
    <div class="row g-3">
        <div class="{{ $trendCol }}">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-graph-up-arrow text-primary"></i>
                    <span>{{ __('ui.charts.enrollment_trend') }}</span>
                </div>
                <div class="dashboard-chart-card__body" style="height:180px">
                    <canvas id="chartEnrollmentTrend" aria-label="{{ __('ui.charts.enrollment_trend') }}"></canvas>
                </div>
            </div>
        </div>
        @if($hasPayments)
        <div class="{{ $trendCol }}">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-currency-exchange text-success"></i>
                    <span>{{ __('ui.charts.payments_trend') }}</span>
                </div>
                <div class="dashboard-chart-card__body" style="height:180px">
                    <canvas id="chartPaymentsTrend" aria-label="{{ __('ui.charts.payments_trend') }}"></canvas>
                </div>
            </div>
        </div>
        <div class="{{ $trendCol }}">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-wallet2 text-warning"></i>
                    <span>{{ __('ui.charts.fee_balance') }}</span>
                </div>
                <div class="dashboard-chart-card__body dashboard-chart-card__body--balance">
                    <div style="height:120px;max-width:140px;margin:0 auto">
                        <canvas id="chartBalance" aria-label="{{ __('ui.charts.fee_balance') }}"></canvas>
                    </div>
                    <div class="dashboard-balance-stats">
                        <div>
                            <span class="text-muted d-block small">{{ __('ui.charts.cleared') }}</span>
                            <strong class="text-success">{{ number_format($chartBalance['cleared'] ?? 0) }}</strong>
                        </div>
                        <div>
                            <span class="text-muted d-block small">{{ __('ui.charts.arrears') }}</span>
                            <strong class="text-danger">{{ number_format($chartBalance['in_arrears'] ?? 0) }}</strong>
                        </div>
                        <div>
                            <span class="text-muted d-block small">{{ __('ui.charts.total_due') }}</span>
                            <strong>{{ number_format($chartBalance['total_arrears'] ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($hasAcademics)
        <div class="{{ $trendCol }}">
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-card__head">
                    <i class="bi bi-ui-checks text-info"></i>
                    <span>{{ __('ui.charts.registration_trend') }}</span>
                </div>
                <div class="dashboard-chart-card__body" style="height:180px">
                    <canvas id="chartRegistrationTrend" aria-label="{{ __('ui.charts.registration_trend') }}"></canvas>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.dashboard-analytics__title { font-size: .7rem; }
.dashboard-chart-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid rgba(226, 232, 240, .95);
    box-shadow: 0 4px 20px rgba(10, 22, 40, .06);
    height: 100%;
    overflow: hidden;
}
.dashboard-chart-card__head {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .65rem 1rem;
    font-size: .8125rem;
    font-weight: 600;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}
.dashboard-chart-card__body { padding: .75rem 1rem 1rem; position: relative; }
.dashboard-chart-card__foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem .75rem;
    padding: .5rem 1rem .75rem;
    font-size: .75rem;
    color: #475569;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
}
.dashboard-chart-card__body--balance {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: center;
    gap: .75rem;
    min-height: 180px;
}
.dashboard-balance-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .5rem;
    text-align: center;
    font-size: .8125rem;
}
.dashboard-stat-grid .dashboard-stat-card {
    background: #fff;
    border-radius: 1rem;
    padding: 1.1rem 1.25rem;
    border: 1px solid rgba(226, 232, 240, .9);
    box-shadow: 0 4px 16px rgba(10, 22, 40, .05);
    height: 100%;
}
.dashboard-stat-grid .dashboard-stat-card__icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: .75rem;
}
.dashboard-stat-grid .dashboard-stat-card__value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
    color: #0f172a;
}
.dashboard-stat-grid .dashboard-stat-card__label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #64748b;
    font-weight: 600;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    var navy = '#1a4fb5';
    var studentsLabel = {!! json_encode($chartStudentsLabel) !!};
    var balanceLabels = {!! json_encode([__('ui.charts.cleared'), __('ui.charts.arrears')]) !!};
    var compactLegend = { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } };
    var compactScale = { ticks: { font: { size: 10 }, precision: 0 }, grid: { color: 'rgba(226,232,240,0.6)' } };

    var barCountPlugin = {
        id: 'barCountLabels',
        afterDatasetsDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (bar, index) {
                        var val = dataset.data[index];
                        if (val == null || val === 0) return;
                        ctx.save();
                        ctx.fillStyle = '#334155';
                        ctx.font = 'bold 11px system-ui, sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        var pos = bar.tooltipPosition();
                        ctx.fillText(val, pos.x, pos.y - 4);
                        ctx.restore();
                    });
                }
            });
        }
    };

    var levelLabels = {!! json_encode($levelLabels) !!};
    var levelData = {!! json_encode($levelData) !!};
    var levelColors = {!! json_encode($levelColors) !!};

    function barChart(id, labels, data, colors, maxY) {
        var el = document.getElementById(id);
        if (!el || !data.length) return;
        var suggestedMax = maxY || Math.max.apply(null, data) * 1.15 || 10;
        new Chart(el, {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: studentsLabel, data: data, backgroundColor: colors, borderRadius: 6, maxBarThickness: 52 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: compactScale,
                    y: Object.assign({ beginAtZero: true, suggestedMax: suggestedMax }, compactScale)
                }
            },
            plugins: [barCountPlugin]
        });
    }

    barChart('chartBarNtaLevel', levelLabels, levelData, levelColors);

    var progLabels = {!! json_encode($progLabels) !!};
    var progData = {!! json_encode($progData) !!};
    var progColors = {!! json_encode(array_slice($progColors, 0, count($progData))) !!};
    if (document.getElementById('chartPieProgramme') && progData.length) {
        new Chart(document.getElementById('chartPieProgramme'), {
            type: 'doughnut',
            data: { labels: progLabels, datasets: [{ data: progData, backgroundColor: progColors, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '52%', plugins: { legend: compactLegend } }
        });
    }

    barChart('chartBarCmtLevel', levelLabels, {!! json_encode($cmtData) !!}, {!! json_encode([$cmtColor, $cmtColor, $cmtColor]) !!});
    barChart('chartBarMltLevel', levelLabels, {!! json_encode($mltData) !!}, {!! json_encode([$mltColor, $mltColor, $mltColor]) !!});

    var enrollLabels = {!! json_encode(array_keys($chartEnrollment ?? [])) !!};
    var enrollData = {!! json_encode(array_values($chartEnrollment ?? [])) !!};
    if (document.getElementById('chartEnrollmentTrend') && enrollData.length) {
        new Chart(document.getElementById('chartEnrollmentTrend'), {
            type: 'bar',
            data: { labels: enrollLabels, datasets: [{ label: studentsLabel, data: enrollData, backgroundColor: 'rgba(26, 79, 181, 0.78)', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: compactScale, y: Object.assign({ beginAtZero: true }, compactScale) } }
        });
    }

    @if(auth()->user()->canModule('finance_payments', 'view'))
    var payLabels = {!! json_encode(array_keys($chartPayments ?? [])) !!};
    var payData = {!! json_encode(array_values($chartPayments ?? [])) !!};
    if (document.getElementById('chartPaymentsTrend') && payData.length) {
        new Chart(document.getElementById('chartPaymentsTrend'), {
            type: 'line',
            data: { labels: payLabels, datasets: [{ label: 'TZS', data: payData, borderColor: navy, backgroundColor: 'rgba(26,79,181,0.12)', fill: true, tension: 0.35, pointRadius: 3 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: compactScale, y: Object.assign({ beginAtZero: true }, compactScale) } }
        });
    }

    var balCleared = {{ (int) ($chartBalance['cleared'] ?? 0) }};
    var balArrears = {{ (int) ($chartBalance['in_arrears'] ?? 0) }};
    if (document.getElementById('chartBalance') && (balCleared + balArrears) > 0) {
        new Chart(document.getElementById('chartBalance'), {
            type: 'doughnut',
            data: {
                labels: balanceLabels,
                datasets: [{ data: [balCleared, balArrears], backgroundColor: ['rgba(29, 157, 87, 0.88)', 'rgba(220, 38, 38, 0.85)'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } } } }
        });
    }
    @endif

    @if(auth()->user()->canAccessAcademics())
    var regLabels = {!! json_encode(array_keys($chartRegistrationTrend ?? [])) !!};
    var regData = {!! json_encode(array_values($chartRegistrationTrend ?? [])) !!};
    if (document.getElementById('chartRegistrationTrend')) {
        new Chart(document.getElementById('chartRegistrationTrend'), {
            type: 'line',
            data: { labels: regLabels, datasets: [{ label: '{{ __('ui.charts.registration_trend') }}', data: regData, borderColor: '#38b6e8', backgroundColor: 'rgba(56,182,232,0.15)', fill: true, tension: 0.35, pointRadius: 3 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: compactScale, y: Object.assign({ beginAtZero: true }, compactScale) } }
        });
    }
    @endif
})();
</script>
@endpush
