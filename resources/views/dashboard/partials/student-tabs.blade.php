@php
    $sd = $studentDashboard ?? [];
    $info = $sd['student_info'] ?? [];
    $paymentGroups = $sd['payment_groups'] ?? [];
    $loan = $sd['loan_details'] ?? ['is_loan_beneficiary' => false, 'items' => []];
    $permissions = $sd['permission_requests'] ?? [];
    $hasFeeItems = collect($paymentGroups)->contains(fn ($g) => count($g['items'] ?? []) > 0);
@endphp

<div class="row g-3 sd-tabs-row">
    <div class="col-lg-8">
        <div class="sd-card sd-tabs-card">
            <ul class="nav nav-tabs sd-tabs" id="studentDashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-info" data-bs-toggle="tab" data-bs-target="#pane-info" type="button" role="tab" aria-controls="pane-info" aria-selected="false">My Information</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-payments" data-bs-toggle="tab" data-bs-target="#pane-payments" type="button" role="tab" aria-controls="pane-payments" aria-selected="true">Payment Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-loan" data-bs-toggle="tab" data-bs-target="#pane-loan" type="button" role="tab" aria-controls="pane-loan" aria-selected="false">Loan Details</button>
                </li>
            </ul>
            <div class="tab-content sd-tab-panels" id="studentDashboardTabsContent">
                <div class="tab-pane fade" id="pane-info" role="tabpanel" aria-labelledby="tab-info" tabindex="0">
                    <div class="sd-info-list">
                        @foreach($info as $row)
                        <div class="sd-info-row">
                            <span class="sd-info-label">{{ $row['label'] }}</span>
                            <span class="sd-info-value">{{ $row['value'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="p-3 border-top bg-light">
                        <a href="{{ auth()->user()->student ? route('students.show', auth()->user()->student) : '#' }}" class="btn btn-sm btn-primary">View full profile</a>
                    </div>
                </div>

                <div class="tab-pane fade show active" id="pane-payments" role="tabpanel" aria-labelledby="tab-payments" tabindex="0">
                    @forelse($paymentGroups as $group)
                    <div class="sd-payment-semester">
                        <div class="sd-payment-semester-head">
                            <h3 class="sd-payment-semester-title">{{ $group['title'] }}</h3>
                            <p class="sd-payment-semester-sub mb-0">{{ $group['subtitle'] }}</p>
                        </div>
                        @foreach($group['items'] as $item)
                            @if(($item['kind'] ?? 'fee') === 'requirement')
                            <article class="sd-payment-entry sd-payment-requirement">
                                <header class="sd-payment-head">
                                    <span class="sd-fee-badge sd-fee-badge-req">Requirement</span>
                                    <span class="badge bg-{{ $item['status_class'] }}">{{ $item['status_label'] }}</span>
                                </header>
                                <p class="fw-semibold mb-1">{{ $item['fee_label'] }}</p>
                                <p class="small text-muted mb-0">{{ $item['detail'] }}</p>
                            </article>
                            @else
                            <article class="sd-payment-entry">
                                <header class="sd-payment-head">
                                    <div class="sd-payment-head-left">
                                        <strong class="sd-payment-index">#{{ $item['index'] }}</strong>
                                        <span class="sd-payment-expiry">{{ $item['expiry_display'] }}</span>
                                    </div>
                                    <span class="sd-fee-badge">{{ $item['fee_label'] }}</span>
                                </header>
                                <dl class="sd-payment-dl">
                                    <div class="sd-payment-dl-row">
                                        <dt>Control Number</dt>
                                        <dd><code>{{ $item['control_number'] }}</code></dd>
                                    </div>
                                    <div class="sd-payment-dl-row">
                                        <dt>Billed Amount</dt>
                                        <dd>{{ number_format($item['billed'], 2) }}</dd>
                                    </div>
                                    <div class="sd-payment-dl-row">
                                        <dt>Paid Amount</dt>
                                        <dd class="text-paid">{{ number_format($item['paid'], 2) }}</dd>
                                    </div>
                                    <div class="sd-payment-dl-row">
                                        <dt>Balance</dt>
                                        <dd class="{{ $item['balance'] > 0 ? 'text-balance' : 'text-paid' }}">{{ number_format($item['balance'], 2) }}</dd>
                                    </div>
                                </dl>
                            </article>
                            @endif
                            @if(!$loop->last)
                            <div class="sd-payment-sep" aria-hidden="true">&bull; &bull; &bull;</div>
                            @endif
                        @endforeach
                    </div>
                    @if(!$loop->last)
                    <div class="sd-payment-semester-divider" aria-hidden="true"></div>
                    @endif
                    @empty
                    <p class="text-muted text-center py-5 mb-0 px-3">No fee schedule is set for your programme this session. Contact the accounts office.</p>
                    @endforelse
                    @if($hasFeeItems && auth()->user()->student)
                    <div class="p-3 border-top">
                        <a href="{{ route('students.ledger', auth()->user()->student) }}" class="btn btn-sm btn-outline-primary">Financial statement</a>
                    </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="pane-loan" role="tabpanel" aria-labelledby="tab-loan" tabindex="0">
                    @if(($loan['is_loan_beneficiary'] ?? false) || count($loan['items'] ?? []) > 0)
                    <ul class="list-unstyled sd-loan-list mb-0">
                        @foreach($loan['items'] as $item)
                        <li class="sd-loan-item">
                            <strong>{{ $item['label'] }}</strong>
                            <span>{{ $item['value'] }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-muted text-center py-5 mb-0 px-3">No loan or instalment plan is recorded for your account.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sd-card sd-permissions-card h-100">
            <div class="sd-permissions-body">
                @if(count($permissions) === 0)
                <h3 class="sd-permissions-title">No Permission Requests</h3>
                <p class="sd-permissions-text mb-0">You don't have any permission requests at the moment.</p>
                @else
                <h3 class="sd-permissions-title">Permission requests</h3>
                <ul class="list-unstyled mb-0 sd-permission-list">
                    @foreach($permissions as $req)
                    <li class="sd-permission-item">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <strong>{{ $req['from'] }} – {{ $req['to'] }}</strong>
                            <span class="badge bg-{{ $req['status_class'] }}">{{ $req['status'] }}</span>
                        </div>
                        <p class="small text-muted mb-0">{{ Str::limit($req['reason'], 120) }}</p>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>
