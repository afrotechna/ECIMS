<style>
    .fin-kpi-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .fin-kpi {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        box-shadow: 0 1px 3px rgba(10, 22, 40, .06);
    }
    .fin-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .fin-kpi-icon--navy { background: linear-gradient(135deg, #e8f4fc 0%, #dbeafe 100%); color: #0d3651; }
    .fin-kpi-icon--green { background: #dcfce7; color: #15803d; }
    .fin-kpi-icon--amber { background: #fef3c7; color: #b45309; }
    .fin-kpi-icon--rose { background: #ffe4e6; color: #be123c; }
    .fin-kpi-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-bottom: .2rem;
    }
    .fin-kpi-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
    }
    .fin-kpi-sub { font-size: .75rem; color: #94a3b8; margin-top: .15rem; }

    .fin-filter-card .form-label { font-size: .75rem; font-weight: 600; color: #475569; margin-bottom: .25rem; }
    .fin-filter-presets { display: flex; flex-wrap: wrap; gap: .35rem; }
    .fin-filter-presets .btn { font-size: .75rem; padding: .25rem .55rem; }

    .fin-report-table { width: 100%; margin: 0; }
    .fin-report-table thead th {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
        padding: .65rem 1rem;
    }
    .fin-report-table tbody td {
        padding: .75rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: .875rem;
    }
    .fin-report-table tbody tr:last-child td { border-bottom: none; }
    .fin-report-table tbody tr:hover { background: #f8fafc; }
    .fin-money { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
    .fin-money--lg { font-size: 1rem; font-weight: 700; color: #0f172a; }
    .fin-progress-wrap { min-width: 80px; }
    .fin-progress { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
    .fin-progress-bar { height: 100%; border-radius: 3px; background: linear-gradient(90deg, #0d3651, #1e4a7a); }
    .fin-progress-bar--warn { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .fin-progress-bar--ok { background: linear-gradient(90deg, #16a34a, #15803d); }

    .fin-method-pills { display: flex; flex-wrap: wrap; gap: .5rem; }
    .fin-method-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .65rem;
        border-radius: 2rem;
        background: #f1f5f9;
        font-size: .8125rem;
        font-weight: 600;
        color: #334155;
    }
    .fin-method-pill strong { color: #0d3651; }

    .fin-prog-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: .75rem;
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .fin-prog-row:last-child { border-bottom: none; }
    @media (min-width: 576px) {
        .fin-prog-row {
            grid-template-columns: 1fr auto;
            align-items: center;
        }
    }
    .fin-prog-name { font-weight: 600; color: #0f172a; margin-bottom: .25rem; }
    .fin-prog-meta { font-size: .8125rem; color: #64748b; }

    .fin-pay-card {
        border: 1px solid #e2e8f0;
        border-radius: .5rem;
        padding: .85rem 1rem;
        margin-bottom: .65rem;
        background: #fff;
    }
    .fin-pay-card:last-child { margin-bottom: 0; }
    .fin-pay-card-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .5rem;
        margin-bottom: .5rem;
    }
    .fin-pay-card-amount { font-size: 1.1rem; font-weight: 800; color: #0d3651; font-variant-numeric: tabular-nums; }
    .fin-pay-card-dl {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .35rem .75rem;
        margin: 0;
        font-size: .8125rem;
    }
    .fin-pay-card-dl dt { color: #64748b; font-weight: 500; margin: 0; }
    .fin-pay-card-dl dd { margin: 0; font-weight: 600; color: #0f172a; text-align: right; }

    @media (min-width: 992px) {
        .fin-pay-cards-only { display: none; }
    }
    @media (max-width: 991.98px) {
        .fin-pay-table-only { display: none; }
    }
</style>
