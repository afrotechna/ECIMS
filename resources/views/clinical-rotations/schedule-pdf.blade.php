<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>NTA {{ $payload['nta_level'] }} clinical rotation schedule</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; margin: 12px; }
        h1 { font-size: 13pt; margin: 0 0 6px; }
        h2 { font-size: 11pt; margin: 10px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px; vertical-align: top; }
        th { background: #eee; font-weight: bold; }
        .schedule-table td { text-align: center; font-weight: bold; }
        .schedule-table td:first-child, .schedule-table th:first-child { text-align: left; font-weight: normal; }
        .schedule-table td:nth-child(2), .schedule-table th:nth-child(2) { text-align: left; font-weight: normal; }
        .legend { font-size: 8.5pt; margin-top: 10px; }
    </style>
</head>
<body>
@include('clinical-rotations.partials.schedule-doc-body', [
    'payload' => $payload,
    'programmeName' => $programmeName ?? null,
    'semesterName' => $semesterName ?? null,
    'usedDefaultStart' => false,
    'footerNote' => 'Hospital placements and student rosters are managed on each rotation round in COHAS.',
])
</body>
</html>
