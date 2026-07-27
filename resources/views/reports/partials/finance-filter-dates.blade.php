@php
    $from = $from ?? now()->startOfMonth()->format('Y-m-d');
    $to = $to ?? now()->format('Y-m-d');
    $formAction = $formAction ?? url()->current();
@endphp
<div class="card card-landing mb-3 fin-filter-card">
    <div class="card-header-landing py-2"><i class="bi bi-funnel me-2"></i>Filter</div>
    <div class="card-body">
        <form method="GET" action="{{ $formAction }}" class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" required>
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Apply</button>
            </div>
            <div class="col-12 col-md">
                <label class="form-label d-block">Quick range</label>
                <div class="fin-filter-presets">
                    <a href="{{ $formAction }}?from={{ now()->format('Y-m-d') }}&to={{ now()->format('Y-m-d') }}" class="btn btn-outline-secondary btn-sm">Today</a>
                    <a href="{{ $formAction }}?from={{ now()->startOfWeek()->format('Y-m-d') }}&to={{ now()->format('Y-m-d') }}" class="btn btn-outline-secondary btn-sm">This week</a>
                    <a href="{{ $formAction }}?from={{ now()->startOfMonth()->format('Y-m-d') }}&to={{ now()->format('Y-m-d') }}" class="btn btn-outline-secondary btn-sm">This month</a>
                    <a href="{{ $formAction }}?from={{ now()->startOfYear()->format('Y-m-d') }}&to={{ now()->format('Y-m-d') }}" class="btn btn-outline-secondary btn-sm">Year to date</a>
                </div>
            </div>
        </form>
    </div>
</div>
