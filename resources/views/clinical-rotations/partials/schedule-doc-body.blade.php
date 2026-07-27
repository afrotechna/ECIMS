@php
    /** @var array $payload from ClinicalRotationScheduleTemplate::documentPayload */
    $p = $payload;
    $gc = (int) $p['group_count'];
    $bc = $gc;
@endphp

@if(!empty($usedDefaultStart))
    <div class="schedule-default-start-banner no-print" style="background:#fff3cd;padding:0.35rem 0.5rem;margin-bottom:0.75rem;font-size:0.85rem;">
        No posting Monday was stored on this round — the schedule uses <strong>this week’s Monday</strong>. Use <code>?start=YYYY-MM-DD</code> (a Monday) to align dates.
    </div>
@endif

<p style="font-size:0.75rem;margin:0 0 0.5rem;color:#555;"><strong>NTA Clinical Rotation Schedule</strong> · Confidential – for internal use only</p>

<h1 style="font-size:1.15rem;margin:0 0 0.35rem;">NTA Level {{ $p['nta_level'] }} — Clinical Rotation Schedule</h1>
<div class="schedule-sub" style="font-size:0.9rem;margin-bottom:0.75rem;">
    @if(!empty($programmeName))
        <strong>Programme:</strong> {{ $programmeName }}<br>
    @endif
    @if(!empty($semesterName))
        <strong>Semester:</strong> {{ $semesterName }}<br>
    @endif
    <strong>Duration:</strong> {{ $p['total_weeks'] }} weeks &nbsp;·&nbsp; <strong>Time per department posting:</strong> {{ $p['weeks_per_block'] }} week{{ $p['weeks_per_block'] > 1 ? 's' : '' }} &nbsp;·&nbsp; <strong>Working days:</strong> Monday – Friday only<br>
    <strong>Start date:</strong> {{ $p['start_monday']->format('l, d M Y') }}
</div>

<h2 style="font-size:1rem;margin-top:1rem;">Weekly department allocation (each row = one Mon–Fri week)</h2>
<table class="schedule-table" style="width:100%;border-collapse:collapse;font-size:0.82rem;margin-top:0.35rem;">
    <thead>
        <tr>
            <th style="border:1px solid #222;padding:0.3rem;text-align:left;background:#eee;">Week</th>
            <th style="border:1px solid #222;padding:0.3rem;text-align:left;background:#eee;">Dates</th>
            @for($g = 1; $g <= $gc; $g++)
                <th style="border:1px solid #222;padding:0.3rem;text-align:center;background:#eee;">Group {{ $g }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($p['block_rows'] as $row)
            <tr>
                <td style="border:1px solid #222;padding:0.3rem;">{{ $row['week_label'] }}</td>
                <td style="border:1px solid #222;padding:0.3rem;">{{ $row['date_range'] }}</td>
                @foreach($row['cells'] as $cell)
                    <td style="border:1px solid #222;padding:0.3rem;text-align:center;font-weight:600;">{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<h2 style="font-size:1rem;margin-top:1.25rem;">Rotation summary by group</h2>
<table class="summary-table" style="width:100%;border-collapse:collapse;margin-top:0.35rem;">
    <thead>
        <tr>
            <th style="border:1px solid #222;padding:0.3rem;text-align:left;background:#eee;width:6rem;">Group</th>
            <th style="border:1px solid #222;padding:0.3rem;text-align:left;background:#eee;">Rotation order (full cycle)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($p['group_order_lines'] as $slot => $line)
            <tr>
                <td style="border:1px solid #222;padding:0.3rem;font-weight:600;">Group {{ $slot }}</td>
                <td style="border:1px solid #222;padding:0.3rem;">{{ $line }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="legend" style="font-size:0.78rem;margin-top:1rem;color:#333;">
    <strong>Abbreviations:</strong>
    @foreach($p['legend'] as $abbr => $full)
        <strong>{{ $abbr }}</strong> = {{ $full }}{{ !$loop->last ? '; ' : '' }}
    @endforeach
    <br><br>
    <em>{{ $footerNote ?? 'Hospital placements and the student roster for each group are managed on the rotation round in COHAS.' }}</em>
</div>
