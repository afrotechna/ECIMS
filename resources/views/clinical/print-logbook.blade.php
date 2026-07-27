<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Clinical logbook — {{ $student->full_name }}</title>
    <style>
        body { font-family: system-ui, sans-serif; font-size: 11pt; margin: 1.5cm; color: #111; }
        h1 { font-size: 16pt; margin: 0 0 0.25rem; }
        .meta { color: #444; font-size: 10pt; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f0f4f8; }
        .sign { margin-top: 2rem; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print"><button onclick="window.print()">Print / Save as PDF</button></p>
    <h1>{{ config('college.institution_name', 'Musoma COHAS') }}</h1>
    <p class="meta">Clinical logbook · {{ $student->full_name }} · {{ $student->reg_no }} · {{ $student->programme?->code }} · NTA {{ $student->nta_level }}</p>

    <p><strong>Competency checklist:</strong>
        @if($overview['competency_met'] ?? false) All required procedures met.
        @else Incomplete — see table below.
        @endif
    </p>

    @if(! empty($overview['competency']))
    <table>
        <thead><tr><th>Procedure</th><th>Required</th><th>Approved</th></tr></thead>
        <tbody>
            @foreach($overview['competency'] as $item)
            <tr>
                <td>{{ $item['procedure']->code }} {{ $item['procedure']->name }}</td>
                <td>{{ $item['required'] }}</td>
                <td>{{ $item['approved'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h2 style="font-size:12pt;margin-top:1.5rem">Logbook entries</h2>
    <table>
        <thead><tr><th>Date</th><th>Procedure</th><th>Status</th><th>Summary</th><th>Instructor</th></tr></thead>
        <tbody>
            @foreach($entries as $e)
            <tr>
                <td>{{ $e->performed_on->format('d/m/Y') }}</td>
                <td>{{ $e->procedure?->code }}</td>
                <td>{{ \App\Models\ClinicalLogbookEntry::statusLabel($e->status) }}</td>
                <td>{{ Str::limit($e->case_summary, 120) }}</td>
                <td>{{ $e->reviewer?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="sign">
        <p>Clinical Instructor signature: _________________________ Date: __________</p>
        <p>Academic Coordinator: _________________________ Date: __________</p>
    </div>
</body>
</html>
