@extends('layouts.app')
@section('title', 'Live room occupancy')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accommodation</span>
    <span class="mx-2">/</span>
    <span>Live by room</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-people me-2 opacity-90"></i>Live by room</h1>
        <p class="page-subtitle-landing mb-0">Students currently in each room from active allocations (today’s date within from / to). Refreshes automatically about every 20 seconds.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge bg-success rounded-pill d-none d-md-inline" id="occupancy-live-pulse" title="Auto-refresh on">Live</span>
        <small class="text-muted" id="occupancy-updated-at">Updated {{ now()->format('H:i:s') }}</small>
        <a href="{{ route('rooms.index') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-door-open me-1"></i> Room list</a>
    </div>
</div>

@if($hostels->isNotEmpty())
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-funnel me-2"></i>Filter by hostel</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="hostel_id" class="form-label small mb-0">Hostel</label>
                <select name="hostel_id" id="hostel_id" class="form-select form-select-sm w-auto">
                    <option value="">All hostels</option>
                    @foreach($hostels as $h)
                    <option value="{{ $h->id }}" {{ request('hostel_id') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
        </form>
    </div>
</div>
@endif

<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span><i class="bi bi-grid-3x3-gap me-2"></i>Residents per room</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Block</th>
                        <th>Room</th>
                        <th class="text-center">Berths</th>
                        <th class="text-center">Filled</th>
                        <th class="text-center">Vacant</th>
                        <th>Students</th>
                    </tr>
                </thead>
                <tbody id="occupancy-tbody">
                    @forelse($rooms as $room)
                    @php
                        $bm = $blockMeta[$loop->index] ?? ['show' => true, 'rowspan' => 1];
                        $res = $room->effectiveAllocationsNow->unique('student_id');
                        $occ = $res->count();
                        $vac = max(0, $room->bed_count - $occ);
                    @endphp
                    <tr data-room-id="{{ $room->id }}">
                        @if($bm['show'])
                        <td rowspan="{{ $bm['rowspan'] }}" class="bg-light align-middle">{{ $room->blockLabel() ?? '—' }}</td>
                        @endif
                        <td><strong class="font-monospace small">{{ $room->name }}</strong></td>
                        <td class="text-center" data-bed>{{ $room->bed_count }}</td>
                        <td class="text-center" data-occupied>{{ $occ }}</td>
                        <td class="text-center" data-vacant-wrap>
                            <span data-vacant>{{ $vac }}</span>
                            @if($room->bed_count > 0 && $occ >= $room->bed_count)
                            <div class="mt-1" data-full-wrap><span class="badge text-bg-secondary">Fully occupied</span></div>
                            @endif
                        </td>
                        <td data-students class="small">
                            @if($res->isEmpty())
                                <span class="text-muted">—</span>
                            @else
                                <ul class="list-unstyled mb-0">
                                    @foreach($res as $a)
                                    <li class="mb-1">
                                        <span class="fw-medium">{{ $a->student->full_name }}</span>
                                        <span class="text-muted">({{ $a->student->reg_no }})</span>
                                        @if($a->student->phone)
                                            <br><span class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $a->student->phone }}</span>
                                        @endif
                                        @if($a->student->programme)
                                            <br><span class="text-muted">{{ $a->student->programme->name }}</span>
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No active rooms match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var url = @json($liveDataUrl);
    var tbody = document.getElementById('occupancy-tbody');
    var updatedEl = document.getElementById('occupancy-updated-at');
    if (!tbody || !url) return;

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function renderStudents(students) {
        if (!students || !students.length) {
            return '<span class="text-muted">—</span>';
        }
        var html = '<ul class="list-unstyled mb-0">';
        students.forEach(function (st) {
            html += '<li class="mb-1"><span class="fw-medium">' + escapeHtml(st.full_name) + '</span> ';
            html += '<span class="text-muted">(' + escapeHtml(String(st.reg_no)) + ')</span>';
            if (st.phone) {
                html += '<br><span class="text-muted"><i class="bi bi-telephone me-1"></i>' + escapeHtml(String(st.phone)) + '</span>';
            }
            if (st.programme && st.programme !== '—') {
                html += '<br><span class="text-muted">' + escapeHtml(st.programme) + '</span>';
            }
            html += '</li>';
        });
        html += '</ul>';
        return html;
    }

    function renderVacantCell(vacant, fullyOccupied) {
        var html = '<span data-vacant>' + String(vacant) + '</span>';
        if (fullyOccupied) {
            html += '<div class="mt-1" data-full-wrap><span class="badge text-bg-secondary">Fully occupied</span></div>';
        }
        return html;
    }

    function applyPayload(data) {
        if (!data || !data.rooms) return;
        if (updatedEl && data.updated_at) {
            try {
                var d = new Date(data.updated_at);
                updatedEl.textContent = 'Updated ' + d.toLocaleTimeString();
            } catch (e) {
                updatedEl.textContent = 'Updated ' + new Date().toLocaleTimeString();
            }
        }
        var map = {};
        data.rooms.forEach(function (r) { map[r.id] = r; });
        tbody.querySelectorAll('tr[data-room-id]').forEach(function (tr) {
            var id = parseInt(tr.getAttribute('data-room-id'), 10);
            var row = map[id];
            if (!row) return;
            var o = tr.querySelector('[data-occupied]');
            var wrap = tr.querySelector('[data-vacant-wrap]');
            var s = tr.querySelector('[data-students]');
            if (o) o.textContent = String(row.occupied);
            if (wrap) wrap.innerHTML = renderVacantCell(row.vacant, !!row.fully_occupied);
            if (s) s.innerHTML = renderStudents(row.students);
        });
    }

    function tick() {
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(applyPayload)
            .catch(function () { /* ignore */ });
    }

    var intervalMs = 20000;
    var timer = setInterval(tick, intervalMs);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') tick();
    });
})();
</script>
@endpush
