@php
    $sd = $studentDashboard ?? [];
    $gpa = (float) ($sd['overall_gpa'] ?? 0);
    $gpaPct = min(100, max(0, ($gpa / \App\Support\GradingScale::MAX_GPA) * 100));
    $completed = (int) ($sd['modules_completed'] ?? 0);
    $withMarks = max(1, (int) ($sd['modules_with_marks'] ?? 1));
    $completedPct = min(100, (int) round(($completed / $withMarks) * 100));
@endphp

@push('styles')
<style>
    .sd-summary-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 1px 4px rgba(10, 22, 40, .08);
        border: 1px solid #e8eef5;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .sd-summary-inner {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
    }
    .sd-stats-row {
        flex: 1 1 280px;
        display: flex;
        flex-wrap: wrap;
        border-right: 1px solid #eef2f7;
    }
    .sd-stat {
        flex: 1 1 33.333%;
        min-width: 120px;
        padding: 1.1rem 1rem;
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .sd-stat:nth-child(3n) { border-right: none; }
    @media (min-width: 768px) {
        .sd-stat { border-bottom: none; border-right: 1px solid #f1f5f9; }
        .sd-stat:last-child { border-right: none; }
    }
    .sd-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .sd-stat-icon.blue { background: #e8f4fc; color: #0d3651; }
    .sd-stat-icon.amber { background: #fef3c7; color: #b45309; }
    .sd-stat-icon.green { background: #dcfce7; color: #15803d; }
    .sd-stat-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; line-height: 1.3; }
    .sd-stat-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .sd-stat-sub { font-size: .75rem; color: #94a3b8; }

    .sd-academic-panel {
        flex: 1 1 260px;
        padding: 1.25rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        justify-content: space-between;
    }
    .sd-ay-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: .05em; }
    .sd-ay-value { font-size: 1rem; font-weight: 700; color: #0d3651; margin-bottom: .15rem; }
    .sd-level { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 .5rem; }
    .sd-badge-active { background: #dcfce7; color: #166534; }
    .sd-badge-graduate { background: #dbeafe; color: #1e40af; }
    .sd-badge-warning { background: #fef3c7; color: #92400e; }
    .sd-badge-muted { background: #f1f5f9; color: #475569; }
    .sd-standing {
        display: inline-block;
        padding: .25rem .65rem;
        border-radius: 2rem;
        font-size: .72rem;
        font-weight: 700;
    }

    .sd-gpa-ring {
        position: relative;
        width: 88px;
        height: 88px;
        flex-shrink: 0;
    }
    .sd-gpa-ring svg { transform: rotate(-90deg); }
    .sd-gpa-ring .ring-bg { fill: none; stroke: #e2e8f0; stroke-width: 8; }
    .sd-gpa-ring .ring-fg { fill: none; stroke: #16a34a; stroke-width: 8; stroke-linecap: round; transition: stroke-dashoffset .4s; }
    .sd-gpa-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .sd-gpa-center strong { font-size: 1.1rem; font-weight: 800; color: #0f172a; line-height: 1; }
    .sd-gpa-center small { font-size: .58rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: .02em; }

    .sd-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 1px 4px rgba(10, 22, 40, .08);
        border: 1px solid #e8eef5;
        height: 100%;
    }
    .sd-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .sd-card-header h2 { font-size: 1rem; font-weight: 700; margin: 0; color: #0f172a; }
    .sd-year-pill {
        font-size: .7rem;
        font-weight: 700;
        padding: .3rem .65rem;
        border-radius: .35rem;
        background: #ede9fe;
        color: #5b21b6;
        white-space: nowrap;
    }
    .sd-reg-table { width: 100%; margin: 0; }
    .sd-reg-table th {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        padding: .75rem 1.25rem;
        border-bottom: 1px solid #eef2f7;
        background: #fafbfc;
    }
    .sd-reg-table td { padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .sd-reg-table tr:last-child td { border-bottom: none; }
    .sd-sem-badge {
        width: 32px;
        height: 32px;
        border-radius: .35rem;
        background: linear-gradient(135deg, #f59e0b, #ea580c);
        color: #fff;
        font-weight: 800;
        font-size: .9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: .65rem;
        flex-shrink: 0;
    }
    .sd-sem-title { font-weight: 700; color: #0f172a; font-size: .875rem; text-transform: uppercase; }
    .sd-sem-dates { font-size: .75rem; color: #94a3b8; margin-top: .15rem; }
    .sd-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .65rem;
        border-radius: .35rem;
        font-size: .75rem;
        font-weight: 700;
    }
    .sd-status.secondary { background: #f1f5f9; color: #64748b; }
    .sd-status.success { background: #dcfce7; color: #166534; }
    .sd-status.warning { background: #fef3c7; color: #92400e; }
    .sd-status.info { background: #e0f2fe; color: #0369a1; }
    .sd-status.danger { background: #fee2e2; color: #b91c1c; }
    .sd-status.open { background: #ffedd5; color: #c2410c; }
    .sd-action-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .85rem;
        border-radius: .35rem;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid #cbd5e1;
        color: #475569;
        background: #fff;
    }
    .sd-action-btn:hover:not(.disabled) {
        background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%);
        border-color: #0d3651;
        color: #fff;
    }
    .sd-action-btn.disabled {
        opacity: .55;
        cursor: not-allowed;
        pointer-events: none;
        background: #f8fafc;
    }
    .sd-action-btn.primary:not(.disabled) {
        background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%);
        border-color: #0d3651;
        color: #fff;
    }

    .sd-quick-item {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
        transition: background .15s;
    }
    .sd-quick-item:last-child { border-bottom: none; }
    .sd-quick-item:hover { background: #f8fafc; }
    .sd-quick-icon {
        width: 36px;
        height: 36px;
        border-radius: .5rem;
        background: #ffedd5;
        color: #ea580c;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .sd-quick-title { font-weight: 700; color: #ea580c; font-size: .9rem; margin-bottom: .15rem; }
    .sd-quick-sub { font-size: .75rem; color: #64748b; margin: 0; }

    .sd-secondary { margin-top: 1rem; }

    .sd-tabs-row { margin-top: 1.25rem; }
    .sd-tabs-card { overflow: hidden; }
    .sd-tabs {
        border-bottom: 1px solid #e2e8f0;
        padding: 0 1rem;
        gap: .25rem;
    }
    .sd-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #64748b;
        font-weight: 600;
        font-size: .875rem;
        padding: .85rem 1rem;
        margin-bottom: -1px;
        border-radius: 0;
        background: transparent;
    }
    .sd-tabs .nav-link:hover { color: #0d3651; }
    .sd-tabs .nav-link.active {
        color: #0d3651;
        border-bottom-color: #0d3651;
        background: transparent;
    }
    .sd-tab-panels { padding: 0; }

    .sd-info-list { padding: .5rem 0; }
    .sd-info-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .65rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: .875rem;
    }
    .sd-info-row:last-child { border-bottom: none; }
    .sd-info-label { color: #64748b; font-weight: 500; }
    .sd-info-value { color: #0f172a; font-weight: 600; text-align: right; max-width: 60%; word-break: break-word; }

    .sd-payment-entry { padding: 1rem 1.25rem .25rem; }
    .sd-payment-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .75rem;
        margin-bottom: .75rem;
        flex-wrap: wrap;
    }
    .sd-payment-index { color: #0f172a; font-size: 1rem; margin-right: .5rem; }
    .sd-payment-expiry { font-size: .8rem; color: #16a34a; font-weight: 600; }
    .sd-fee-badge {
        background: linear-gradient(135deg, #0a1628 0%, #0d3651 100%);
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        padding: .35rem .65rem;
        border-radius: .25rem;
        white-space: nowrap;
    }
    .sd-payment-dl { margin: 0; }
    .sd-payment-dl-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .55rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .875rem;
    }
    .sd-payment-dl-row:last-child { border-bottom: none; }
    .sd-payment-dl-row dt { color: #64748b; font-weight: 500; margin: 0; }
    .sd-payment-dl-row dd { margin: 0; font-weight: 600; color: #0f172a; }
    .sd-payment-dl-row .text-paid { color: #16a34a; }
    .sd-payment-dl-row .text-balance { color: #dc2626; }
    .sd-payment-sep {
        text-align: center;
        color: #cbd5e1;
        letter-spacing: .25em;
        padding: .35rem 0;
        font-size: .75rem;
        border-bottom: 1px dashed #e2e8f0;
        margin: 0 1.25rem;
    }
    .sd-payment-semester { border-bottom: 1px solid #e2e8f0; }
    .sd-payment-semester:last-of-type { border-bottom: none; }
    .sd-payment-semester-head {
        padding: 1rem 1.25rem .5rem;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        border-bottom: 1px solid #e2e8f0;
    }
    .sd-payment-semester-title {
        font-size: .9375rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #0d3651;
        margin: 0 0 .35rem;
    }
    .sd-payment-semester-sub { font-size: .8125rem; color: #64748b; line-height: 1.45; }
    .sd-payment-semester-divider { height: .5rem; background: #f1f5f9; }
    .sd-payment-requirement { background: #fafafa; }
    .sd-fee-badge-req { background: #64748b !important; }

    .sd-loan-list { padding: 1rem 1.25rem; }
    .sd-loan-item {
        padding: .75rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .875rem;
    }
    .sd-loan-item:last-child { border-bottom: none; }
    .sd-loan-item strong { display: block; color: #0d3651; margin-bottom: .2rem; }

    .sd-permissions-card { display: flex; flex-direction: column; }
    .sd-permissions-body { padding: 1.5rem 1.25rem; flex: 1; }
    .sd-permissions-title {
        font-size: 1rem;
        font-weight: 700;
        color: #0d3651;
        margin: 0 0 .5rem;
    }
    .sd-permissions-text { color: #64748b; font-size: .875rem; }
    .sd-permission-item {
        padding: .75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .sd-permission-item:last-child { border-bottom: none; }
</style>
@endpush

<div class="sd-summary-card">
    <div class="sd-summary-inner">
        <div class="sd-stats-row">
            <div class="sd-stat">
                <div class="sd-stat-icon blue"><i class="bi bi-pc-display"></i></div>
                <div>
                    <div class="sd-stat-label">Total modules registered</div>
                    <div class="sd-stat-value">{{ $sd['total_modules_registered'] ?? 0 }}</div>
                </div>
            </div>
            <div class="sd-stat">
                <div class="sd-stat-icon amber"><i class="bi bi-lightbulb"></i></div>
                <div>
                    <div class="sd-stat-label">Registered semesters</div>
                    <div class="sd-stat-value">{{ $sd['registered_semester_count'] ?? 0 }}</div>
                </div>
            </div>
            <div class="sd-stat">
                <div class="sd-stat-icon green"><i class="bi bi-award"></i></div>
                <div>
                    <div class="sd-stat-label">Modules completed</div>
                    <div class="sd-stat-value">{{ $completed }} <span class="sd-stat-sub">/ {{ $withMarks }}</span></div>
                </div>
            </div>
        </div>
        <div class="sd-academic-panel">
            <div>
                <div class="sd-ay-label">Academic year</div>
                <div class="sd-ay-value">{{ $sd['academic_year_label'] ?? '' }}</div>
                <p class="sd-level mb-0">{{ $sd['year_of_study'] ?? 'Student' }}</p>
                @php $badge = $sd['standing_badge'] ?? ['text' => 'Active student', 'class' => 'sd-badge-active']; @endphp
                <span class="sd-standing {{ $badge['class'] }}">{{ $badge['text'] }}</span>
            </div>
            <div class="sd-gpa-ring" aria-label="Overall GPA {{ number_format($gpa, 1) }}">
                @php $circ = 2 * 3.14159 * 36; $offset = $circ * (1 - $gpaPct / 100); @endphp
                <svg width="88" height="88" viewBox="0 0 88 88" aria-hidden="true">
                    <circle class="ring-bg" cx="44" cy="44" r="36"></circle>
                    <circle class="ring-fg" cx="44" cy="44" r="36"
                            stroke-dasharray="{{ $circ }}"
                            stroke-dashoffset="{{ $offset }}"></circle>
                </svg>
                <div class="sd-gpa-center">
                    <strong>{{ number_format($gpa, 1) }}</strong>
                    <small>Overall GPA</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="sd-card">
            <div class="sd-card-header">
                <h2><i class="bi bi-ui-checks-grid me-2 text-primary"></i>Online registration</h2>
                <span class="sd-year-pill">Academic year: {{ $sd['academic_year_label'] ?? '' }}</span>
            </div>
            <div class="table-responsive">
                <table class="sd-reg-table">
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sd['registration_rows'] ?? [] as $row)
                        <tr>
                            <td>
                                <div class="d-flex align-items-start">
                                    <span class="sd-sem-badge">{{ $row['period'] }}</span>
                                    <div>
                                        <div class="sd-sem-title">{{ $row['title'] }}</div>
                                        @if($row['dates'])
                                        <div class="sd-sem-dates">{{ $row['dates'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="sd-status {{ $row['status_class'] }}">
                                    <i class="bi {{ $row['status_icon'] }}"></i>
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($row['action_enabled'] && $row['action_url'])
                                <a href="{{ $row['action_url'] }}"
                                   class="sd-action-btn {{ $row['status_key'] === 'open' ? 'primary' : '' }}">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    {{ $row['action_label'] }}
                                </a>
                                @else
                                <span class="sd-action-btn disabled">
                                    <i class="bi bi-slash-circle"></i>
                                    {{ $row['action_label'] }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sd-card">
            <div class="sd-card-header">
                <h2><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick actions</h2>
            </div>
            <div>
                @foreach($sd['quick_actions'] ?? [] as $action)
                <a href="{{ $action['url'] }}" class="sd-quick-item">
                    <span class="sd-quick-icon"><i class="bi {{ $action['icon'] }}"></i></span>
                    <div>
                        <div class="sd-quick-title">{{ $action['title'] }}</div>
                        <p class="sd-quick-sub">
                            {{ $action['subtitle'] }}
                            @if(isset($action['percent']))
                            ({{ $action['percent'] }}%)
                            @endif
                        </p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('dashboard.partials.student-tabs')

@if(isset($announcements) && $announcements->isNotEmpty())
<div class="sd-secondary">
    <div class="sd-card">
        <div class="sd-card-header"><h2><i class="bi bi-megaphone me-2"></i>Announcements</h2></div>
        <div class="p-3">
            <ul class="list-unstyled mb-0">
                @foreach($announcements as $a)
                <li class="mb-3 pb-3 border-bottom">
                    <strong>{{ $a->title }}</strong>
                    @if($a->show_until)<span class="text-muted small"> — until {{ $a->show_until->format('d/m/Y') }}</span>@endif
                    @if($a->body)<p class="mb-0 mt-1 small text-muted">{{ \Illuminate\Support\Str::limit($a->body, 200) }}</p>@endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<div class="sd-secondary">
    <div class="sd-card">
        <div class="sd-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="mb-0"><i class="bi bi-folder2-open me-2"></i>College documents</h2>
            <a href="{{ route('college-documents.index') }}" class="btn btn-sm btn-outline-primary">Browse folders</a>
        </div>
        <div class="p-3">
            @if(isset($publicInstitutionDocuments) && $publicInstitutionDocuments->isNotEmpty())
            <p class="small text-muted mb-2">Recent public files — open folders for practicum guides, curriculum, minutes, and more.</p>
            <ul class="list-group list-group-flush border rounded">
                @foreach($publicInstitutionDocuments->take(5) as $doc)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $doc->title }} <span class="badge bg-light text-dark ms-1">{{ $doc->categoryLabel() }}</span></span>
                    <a href="{{ route('institution-documents.download', $doc) }}" class="btn btn-sm btn-outline-primary">Download</a>
                </li>
                @endforeach
            </ul>
            @else
            <p class="small text-muted mb-0">Browse college documents by folder (curriculum, practicum guide, assessment plan, minutes, and more).</p>
            @endif
        </div>
    </div>
</div>
