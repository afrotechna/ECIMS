@if(($practicum_guide ?? null) || ($competency_checklist ?? []) !== [])
@php
    $groups = $competency_checklist_groups ?? [];
    $flat = $competency_checklist ?? [];
    $metTotal = collect($flat)->filter(fn ($i) => $i['met'])->count();
    $allTotal = count($flat);
    $pct = $allTotal > 0 ? round(($metTotal / $allTotal) * 100) : 0;
@endphp
<div class="card card-landing mb-3 border-primary border-opacity-25">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-book me-2"></i>NTA Level {{ $practicum_nta_level ?? ($practicum_guide['nta_level'] ?? 4) }} — CMT practicum guide</span>
        @if($practicum_guide['url'] ?? null)
            <a href="{{ $practicum_guide['url'] }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                <i class="bi bi-file-earmark-pdf me-1"></i> Open full PDF
                @if($practicum_guide['page_count'] ?? null)
                    ({{ $practicum_guide['page_count'] }} pages)
                @endif
            </a>
        @endif
    </div>
    <div class="card-body pb-2">
        <p class="small text-muted mb-2">Marks apply when the whole procedure is approved in your logbook.</p>

        @if($allTotal > 0)
        <div class="competency-checklist-accordion border rounded overflow-hidden mb-2">
            <button
                class="competency-checklist-toggle btn btn-light w-100 d-flex flex-wrap justify-content-between align-items-center gap-2 text-start py-3 px-3 border-0 rounded-0"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#competencyChecklistCollapse"
                aria-expanded="false"
                aria-controls="competencyChecklistCollapse"
            >
                <span class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-clipboard-check text-primary"></i>
                    <strong>Competency checklist</strong>
                    <span class="badge bg-{{ $metTotal === $allTotal && $allTotal > 0 ? 'success' : 'secondary' }}">{{ $metTotal }} / {{ $allTotal }} procedures met</span>
                </span>
                <span class="d-flex align-items-center gap-2 text-muted small">
                    <span class="d-none d-sm-inline">{{ count($groups) }} modules · click to expand</span>
                    <i class="bi bi-chevron-down competency-checklist-chevron transition-chevron"></i>
                </span>
            </button>
            <div class="collapse" id="competencyChecklistCollapse">
                <div class="p-3 border-top bg-light bg-opacity-50">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Overall logbook progress</span>
                            <span class="fw-semibold">{{ $pct }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    @if(!empty($assessment_methods ?? []))
                    <div class="mb-3">
                        <div class="small fw-semibold text-uppercase text-muted mb-1">Modes of assessment (guide)</div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($assessment_methods as $method)
                                <span class="badge bg-white text-dark border small">{{ $method }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="accordion accordion-flush competency-groups-accordion" id="competencyGroupsAccordion">
                        @foreach($groups as $gi => $group)
                        @php
                            $groupId = 'compGroup'.preg_replace('/[^a-zA-Z0-9]/', '', $group['key']);
                            $gMet = $group['met'] ?? 0;
                            $gTotal = $group['total'] ?? 0;
                        @endphp
                        <div class="accordion-item bg-white border mb-1 rounded overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 small" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" aria-expanded="false">
                                    <span class="me-2 text-truncate">{{ $group['title'] }}</span>
                                    <span class="badge bg-{{ $gMet === $gTotal && $gTotal > 0 ? 'success' : 'secondary' }} ms-auto me-2">{{ $gMet }}/{{ $gTotal }}</span>
                                </button>
                            </h2>
                            <div id="{{ $groupId }}" class="accordion-collapse collapse" data-bs-parent="#competencyGroupsAccordion">
                                <div class="accordion-body p-0">
                                    @if(!empty($group['items']) || !empty($group['subsections']))
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 small align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Procedure</th>
                                                    <th class="text-center" style="width: 7rem;">Guide</th>
                                                    <th class="text-center" style="width: 6rem;">Done</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($group['items'] as $item)
                                                @php $p = $item['procedure']; @endphp
                                                <tr class="{{ $item['met'] ? 'table-success table-success-subtle' : '' }}">
                                                    <td>
                                                        <div class="fw-medium">{{ Str::limit($p->name, 90) }}</div>
                                                        <div class="font-monospace text-muted">{{ $p->code }}</div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary procedure-guide-btn" data-procedure-id="{{ $p->id }}" title="What to do — step guide">
                                                            <i class="bi bi-journal-text"></i><span class="d-none d-md-inline ms-1">Guide</span>
                                                        </button>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($item['met'])<i class="bi bi-check-circle-fill text-success"></i>
                                                        @else<span class="text-muted">{{ $item['approved'] }}/{{ $item['required'] }}</span>@endif
                                                    </td>
                                                </tr>
                                                @endforeach

                                                @foreach($group['subsections'] ?? [] as $sub)
                                                @php
                                                    $header = $sub['header_item'] ?? null;
                                                    $hp = $header['procedure'] ?? null;
                                                @endphp
                                                @if($hp && $header)
                                                <tr class="{{ $header['met'] ? 'table-success table-success-subtle' : '' }}">
                                                    <td>
                                                        <div class="fw-medium">{{ $sub['title'] }}</div>
                                                        <div class="font-monospace text-muted">{{ $hp->code }}</div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary procedure-guide-btn" data-procedure-id="{{ $hp->id }}" title="What to do — step guide">
                                                            <i class="bi bi-journal-text"></i><span class="d-none d-md-inline ms-1">Guide</span>
                                                        </button>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($header['met'])<i class="bi bi-check-circle-fill text-success"></i>
                                                        @else<span class="text-muted">{{ $header['approved'] }}/{{ $header['required'] }}</span>@endif
                                                    </td>
                                                </tr>
                                                @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@if(!empty($procedure_guides))
    @include('clinical.partials.procedure-guide-modal')
@endif

@push('styles')
<style>
.competency-checklist-toggle:hover { background-color: #f8fafc !important; }
.competency-checklist-toggle[aria-expanded="true"] .competency-checklist-chevron { transform: rotate(180deg); }
.transition-chevron { transition: transform 0.2s ease; }
.competency-groups-accordion .accordion-button { font-size: 0.875rem; }
.competency-groups-accordion .accordion-button:not(.collapsed) { background: #f0f9ff; }
</style>
@endpush
@endif
