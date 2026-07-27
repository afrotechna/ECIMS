{{-- Top row: Group # — Department | Hospital; then student list --}}
<table class="roster-group-table">
    <thead>
        <tr class="roster-banner">
            <th colspan="2" class="roster-banner-dept">Group {{ $slot }} — {{ $department }}@if(!empty($departmentAbbr) && $departmentAbbr !== $department) <span class="roster-abbr">({{ $departmentAbbr }})</span>@endif</th>
            <th class="roster-banner-hospital">Hospital: {{ $hospital }}</th>
        </tr>
        <tr class="roster-columns">
            <th class="sn">SN</th>
            <th>Student name</th>
            <th style="width:28%">NACTVET / registration no.</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students as $stu)
            <tr>
                <td class="sn">{{ $loop->iteration }}</td>
                <td>{{ $stu->full_name }}</td>
                <td class="roster-reg">{{ $stu->registrationNumberDisplay() ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-empty">No students in this group.</td></tr>
        @endforelse
    </tbody>
</table>
