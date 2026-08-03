@extends('layouts.app')
@section('title', 'Calendar')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Calendar · {{ $academicYearLabel }}</span>
</nav>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 small mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="gcal-wrap">
    {{-- Google-style toolbar --}}
    <div class="gcal-toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
        @if($canManage ?? false)
        <div class="dropdown">
            <button class="btn gcal-create dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-plus-lg"></i> Create
            </button>
            <ul class="dropdown-menu shadow-sm">
                <li><button class="dropdown-item" type="button" data-action="event"><i class="bi bi-calendar-plus me-2"></i>Event</button></li>
                <li><button class="dropdown-item" type="button" data-action="holiday"><i class="bi bi-star me-2"></i>Holiday from list</button></li>
            </ul>
        </div>
        @endif
        <form method="get" class="ms-auto d-flex align-items-center gap-2">
            <label class="small text-muted mb-0 d-none d-md-inline">Academic year</label>
            <select name="academic_year" class="form-select form-select-sm gcal-year" onchange="this.form.submit()" data-no-search>
                @foreach($academicYearOptions as $start => $label)
                <option value="{{ $start }}" @selected($academicYear == $start)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="gcal-layout">
        <div class="gcal-main">
            <div id="cohas-calendar"></div>
        </div>
        <aside class="gcal-aside">
            <h6 class="gcal-aside-title">{{ $academicYearLabel }} · Upcoming</h6>
            @if($semesters->isNotEmpty())
            <div class="gcal-semesters mb-2">
                @foreach($semesters as $s)
                <div class="small text-muted mb-1">
                    <i class="bi bi-calendar-range me-1"></i>{{ $s->periodName() }}
                    @if($s->start_date)
                    <span class="d-block ps-3" style="font-size:.7rem">{{ $s->start_date->format('d/m/Y') }}@if($s->end_date) – {{ $s->end_date->format('d/m/Y') }}@endif</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
            <div class="gcal-aside-list" id="upcoming-events-list">
                @php $upcoming = $customEvents->take(6); @endphp
                @forelse($upcoming as $ev)
                <div class="gcal-aside-item" data-event-id="{{ $ev->id }}">
                    <span class="gcal-dot" style="background:{{ \App\Models\CalendarEvent::colorForType($ev->type, $ev->color) }}"></span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="gcal-aside-item-title">{{ $ev->title }}</div>
                        <div class="gcal-aside-item-date">{{ $ev->starts_on->format('d M') }}</div>
                    </div>
                    @if(($canManage ?? false) && auth()->user()->canModule('calendar', 'delete'))
                    <button type="button" class="btn-close btn-close-sm calendar-delete-btn" data-id="{{ $ev->id }}" aria-label="Remove"></button>
                    @endif
                </div>
                @empty
                <p class="text-muted small mb-0">No events yet.@if($canManage ?? false) Use <strong>Create</strong>.@endif</p>
                @endforelse
            </div>
            <div class="gcal-legend mt-3">
                <span class="gcal-leg" style="--c:#ef4444"></span> Holiday
                <span class="gcal-leg" style="--c:#10b981"></span> College
                <span class="gcal-leg" style="--c:#6366f1"></span> Semester
            </div>
        </aside>
    </div>
</div>

{{-- Event detail (click day) --}}
<div class="modal fade" id="calendarEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content gcal-modal border-0">
            <div class="modal-header border-0 py-2">
                <h6 class="modal-title mb-0" id="calendarEventModalTitle"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <span class="badge text-bg-light text-dark mb-2" id="calendarEventModalType"></span>
                <p class="small text-muted mb-1" id="calendarEventModalDates"></p>
                <p class="small mb-0" id="calendarEventModalDesc"></p>
            </div>
            <div class="modal-footer border-0 pt-0" id="calendarEventModalFooter"></div>
        </div>
    </div>
</div>

@if($canManage ?? false)
{{-- Create event (compact) --}}
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gcal-modal border-0">
            <form id="calendar-add-form" action="{{ route('calendar.events.store') }}" method="POST">
                @csrf
                <input type="hidden" name="all_day" value="1">
                <div class="modal-header border-0">
                    <h6 class="modal-title">New event</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <input type="text" name="title" class="form-control form-control-lg border-0 border-bottom rounded-0 px-0" placeholder="Add title" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small text-muted">Start</label>
                            <input type="date" name="starts_on" id="calendar-starts-on" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">End</label>
                            <input type="date" name="ends_on" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-2">
                        <select name="type" class="form-select form-select-sm">
                            <option value="college">College event</option>
                            <option value="exam">Examination</option>
                            <option value="registration">Registration</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Description (optional)"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn gcal-create btn-sm" id="calendar-form-submit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Pick holiday (compact list) --}}
<div class="modal fade" id="pickHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content gcal-modal border-0">
            <div class="modal-header border-0 py-2">
                <h6 class="modal-title">Add holiday</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pb-2">
                    <input type="search" class="form-control form-control-sm" id="holiday-search" placeholder="Search holidays…">
                </div>
                <div class="gcal-holiday-list" id="holiday-pick-list">
                    @foreach($holidayCatalog ?? [] as $item)
                    @if(!($item['activated'] ?? false))
                    <button type="button" class="gcal-holiday-pick w-100 text-start border-0 border-bottom px-3 py-2 catalog-pick-btn"
                        data-key="{{ $item['key'] }}"
                        data-title="{{ $item['title'] }}"
                        data-date="{{ $item['starts_on'] }}"
                        data-calendar-year="{{ $item['calendar_year'] }}"
                        data-description="{{ e($item['description']) }}">
                        <div class="fw-semibold small">{{ $item['title'] }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ \Carbon\Carbon::parse($item['starts_on'])->format('D, d M Y') }}</div>
                    </button>
                    @endif
                    @endforeach
                </div>
                <p class="small text-muted px-3 py-2 mb-0 d-none" id="holiday-pick-empty">All holidays for {{ $academicYearLabel }} are already on the calendar.</p>
            </div>
        </div>
    </div>
</div>

{{-- Confirm before publish holiday --}}
<div class="modal fade" id="confirmHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content gcal-modal border-0">
            <div class="modal-header border-0">
                <h6 class="modal-title">Publish holiday?</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Everyone (staff & students) will see this on the calendar:</p>
                <div class="gcal-confirm-box p-3 rounded">
                    <div class="fw-bold" id="confirm-holiday-title"></div>
                    <div class="text-muted small" id="confirm-holiday-date"></div>
                    <p class="small mb-0 mt-2" id="confirm-holiday-desc"></p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn gcal-create btn-sm" id="confirm-holiday-publish">Publish to calendar</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
.gcal-wrap { max-width: 900px; }
.gcal-toolbar { min-height: 36px; }
.gcal-create {
    background: var(--cohas-gradient); color: #fff; border: none; border-radius: 24px;
    font-weight: 500; font-size: .8rem; padding: .35rem 1rem;
    box-shadow: 0 1px 3px var(--cohas-shadow);
}
.gcal-create:hover { background: var(--cohas-gradient-hover); color: #fff; }
.gcal-year { width: auto; border-radius: 4px; font-size: .75rem; }
.gcal-layout {
    display: grid; grid-template-columns: 1fr 180px; gap: .75rem;
    background: var(--cohas-surface); border: 1px solid var(--cohas-border);
    border-radius: var(--cohas-radius-lg); box-shadow: 0 4px 20px var(--cohas-shadow);
    overflow: hidden;
}
@media (max-width: 768px) { .gcal-layout { grid-template-columns: 1fr; } .gcal-aside { display: none; } }
.gcal-main { padding: .4rem .6rem .75rem; min-width: 0; }
.gcal-aside {
    border-left: 1px solid var(--cohas-border); padding: .6rem; background: var(--cohas-surface-muted);
}
.gcal-aside-title { font-size: .65rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--cohas-text-muted); margin-bottom: .4rem; }
.gcal-aside-item { display: flex; align-items: flex-start; gap: .4rem; padding: .3rem 0; font-size: .75rem; }
.gcal-aside-item-title { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--cohas-text); }
.gcal-aside-item-date { font-size: .65rem; color: var(--cohas-text-muted); }
.gcal-dot { width: 7px; height: 7px; border-radius: 50%; margin-top: .3rem; flex-shrink: 0; }
.gcal-leg { font-size: .62rem; color: var(--cohas-text-muted); margin-right: .5rem; }
.gcal-leg::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--c); margin-right: 3px; }
#cohas-calendar { max-height: 380px; }
.cohas-fc {
    --fc-border-color: var(--cohas-border);
    --fc-today-bg-color: var(--cohas-blue-soft);
    --fc-page-bg-color: var(--cohas-surface);
    font-size: .75rem;
}
.cohas-fc .fc-toolbar { margin-bottom: .4rem !important; }
.cohas-fc .fc-toolbar-title { font-size: 1rem; font-weight: 500; color: var(--cohas-text); }
.cohas-fc .fc-button {
    background: transparent !important; border: none !important; color: var(--cohas-text-muted) !important;
    font-size: .7rem !important; padding: .2rem .45rem !important; box-shadow: none !important;
}
.cohas-fc .fc-button:hover { background: var(--cohas-hover) !important; border-radius: 50% !important; }
.cohas-fc .fc-button-primary:not(:disabled).fc-button-active { background: var(--cohas-blue-soft) !important; color: var(--cohas-blue-700) !important; border-radius: 4px !important; }
.cohas-fc .fc-col-header-cell { padding: .3rem 0; font-size: .62rem; font-weight: 500; color: var(--cohas-text-muted); }
.cohas-fc .fc-daygrid-day { min-height: 3.75rem; }
.cohas-fc .fc-daygrid-day-number { font-size: .7rem; color: var(--cohas-text); padding: .2rem .35rem; }
.cohas-fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    background: var(--cohas-blue-700); color: #fff; border-radius: 50%; width: 1.35rem; height: 1.35rem;
    display: flex; align-items: center; justify-content: center; margin: 2px;
}
.cohas-fc .fc-event {
    border: none; border-radius: 3px; font-size: .62rem; padding: 0 3px;
    cursor: pointer; line-height: 1.3;
}
.cohas-fc .fc-daygrid-event-dot { display: none; }
.gcal-modal .modal-content { border-radius: var(--cohas-radius-md); }
.gcal-holiday-list { max-height: 260px; overflow-y: auto; }
.gcal-holiday-pick { background: var(--cohas-surface); transition: background .15s; }
.gcal-holiday-pick:hover { background: var(--cohas-hover); }
.gcal-confirm-box { background: var(--cohas-surface-muted); border: 1px solid var(--cohas-border); }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('cohas-calendar');
    if (!el || typeof FullCalendar === 'undefined') return;

    var canManage = @json($canManage ?? false);
    var canDelete = @json(($canManage ?? false) && auth()->user()->canModule('calendar', 'delete'));
    var csrf = @json(csrf_token());
    var academicYearLabel = @json($academicYearLabel);
    var activateUrl = @json(route('calendar.holidays.activate'));
    var destroyUrlTemplate = @json(route('calendar.events.destroy', ['calendar_event' => '__ID__']));

    var eventModal = document.getElementById('calendarEventModal');
    var eventModalBs = eventModal ? new bootstrap.Modal(eventModal) : null;
    var createEventModal = document.getElementById('createEventModal');
    var createEventBs = createEventModal ? new bootstrap.Modal(createEventModal) : null;
    var pickHolidayModal = document.getElementById('pickHolidayModal');
    var pickHolidayBs = pickHolidayModal ? new bootstrap.Modal(pickHolidayModal) : null;
    var confirmHolidayModal = document.getElementById('confirmHolidayModal');
    var confirmHolidayBs = confirmHolidayModal ? new bootstrap.Modal(confirmHolidayModal) : null;

    var pendingHoliday = null;

    var calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        initialDate: @json($calendarInitialDate),
        height: 380,
        contentHeight: 330,
        fixedWeekCount: false,
        firstDay: 1,
        dayMaxEvents: 2,
        moreLinkClick: 'popover',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
        eventSources: [{ url: @json(route('calendar.events.feed')), method: 'GET' }],
        eventClick: function (info) {
            var p = info.event.extendedProps || {};
            document.getElementById('calendarEventModalTitle').textContent = info.event.title;
            document.getElementById('calendarEventModalType').textContent = p.typeLabel || p.type || '';
            var s = info.event.start ? info.event.start.toLocaleDateString(undefined, { dateStyle: 'medium' }) : '';
            document.getElementById('calendarEventModalDates').textContent = s;
            document.getElementById('calendarEventModalDesc').textContent = p.description || '';
            var footer = document.getElementById('calendarEventModalFooter');
            footer.innerHTML = '';
            if (canDelete && p.fromDatabase && p.eventId) {
                var btn = document.createElement('button');
                btn.className = 'btn btn-outline-danger btn-sm';
                btn.textContent = 'Remove';
                btn.onclick = function () {
                    Swal.fire({ title: 'Remove for everyone?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Remove' }).then(function (result) {
                        if (!result.isConfirmed) return;
                        fetch(destroyUrlTemplate.replace('__ID__', p.eventId), {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                        }).then(function (r) { if (r.ok) { eventModalBs.hide(); calendar.refetchEvents(); location.reload(); } });
                    });
                };
                footer.appendChild(btn);
            }
            eventModalBs.show();
        },
        dateClick: function (arg) {
            if (!canManage) return;
            var inp = document.getElementById('calendar-starts-on');
            if (inp) inp.value = arg.dateStr.slice(0, 10);
            createEventBs.show();
        }
    });
    el.classList.add('cohas-fc');
    calendar.render();

    document.querySelectorAll('[data-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.getAttribute('data-action') === 'event') createEventBs.show();
            else if (btn.getAttribute('data-action') === 'holiday') pickHolidayBs.show();
        });
    });

    document.getElementById('holiday-search')?.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.catalog-pick-btn').forEach(function (b) {
            b.style.display = b.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    document.querySelectorAll('.catalog-pick-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingHoliday = {
                key: btn.getAttribute('data-key'),
                title: btn.getAttribute('data-title'),
                date: btn.getAttribute('data-date'),
                calendarYear: btn.getAttribute('data-calendar-year'),
                description: btn.getAttribute('data-description')
            };
            document.getElementById('confirm-holiday-title').textContent = pendingHoliday.title;
            document.getElementById('confirm-holiday-date').textContent = new Date(pendingHoliday.date + 'T12:00:00').toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('confirm-holiday-desc').textContent = pendingHoliday.description;
            pickHolidayBs.hide();
            confirmHolidayBs.show();
        });
    });

    document.getElementById('confirm-holiday-publish')?.addEventListener('click', function () {
        if (!pendingHoliday) return;
        var btn = this;
        btn.disabled = true;
        var body = new FormData();
        body.append('catalog_key', pendingHoliday.key);
        body.append('year', pendingHoliday.calendarYear);
        body.append('_token', csrf);
        fetch(activateUrl, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            btn.disabled = false;
            confirmHolidayBs.hide();
            if (res.ok) {
                pendingHoliday = null;
                calendar.refetchEvents();
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Could not publish', text: res.data.message || 'Could not publish.' });
            }
        })
        .catch(function () { btn.disabled = false; Swal.fire({ icon: 'error', title: 'Network error' }); });
    });

    var form = document.getElementById('calendar-add-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var title = form.querySelector('[name=title]').value.trim();
            if (!title) return;
            Swal.fire({ title: 'Save this event?', text: 'Everyone will see it on the calendar.', icon: 'question', showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d', confirmButtonText: 'Save' }).then(function (result) {
                if (!result.isConfirmed) return;
                var btn = document.getElementById('calendar-form-submit');
                btn.disabled = true;
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (res) {
                    btn.disabled = false;
                    if (res.ok) { createEventBs.hide(); calendar.refetchEvents(); location.reload(); }
                    else Swal.fire({ icon: 'error', title: 'Could not save event' });
                })
                .catch(function () { btn.disabled = false; form.submit(); });
            });
        });
    }

    document.querySelectorAll('.calendar-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            Swal.fire({ title: 'Remove this event?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Remove' }).then(function (result) {
                if (!result.isConfirmed) return;
                fetch(destroyUrlTemplate.replace('__ID__', btn.getAttribute('data-id')), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                }).then(function (r) { if (r.ok) location.reload(); });
            });
        });
    });

    if (canManage && !document.querySelector('.catalog-pick-btn')) {
        document.getElementById('holiday-pick-empty')?.classList.remove('d-none');
    }
});
</script>
@endpush
