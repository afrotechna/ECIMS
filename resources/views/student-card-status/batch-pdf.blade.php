<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student ID cards — batch print</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; color: #0f172a; }
        .section-title { font-size: 9pt; font-weight: bold; color: #475569; margin: 0 0 4mm; }
        table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.grid td { width: 50%; padding: 0 3mm 5mm 0; vertical-align: top; }
        .card-box { width: 100%; height: 2.15in; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; }
        .card-band { background: #0d3651; color: #fff; padding: 1.5mm 2.5mm; }
        table.band-table { width: 100%; border-collapse: collapse; }
        table.band-table td { vertical-align: middle; padding: 0; }
        .band-logo { width: 9mm; }
        .band-logo img { width: 8mm; height: 8mm; }
        .band-logo.right { text-align: right; }
        .band-name { text-align: center; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
        .card-body { padding: 2mm 2.5mm; }
        table.body-table { width: 100%; border-collapse: collapse; }
        table.body-table td { vertical-align: top; padding: 0; }
        .photo-cell { width: 18mm; }
        .photo-cell img { width: 17mm; height: 21mm; border: 1px solid #e2e8f0; }
        .info-cell { padding-left: 2.5mm; }
        .card-name { font-size: 8.5pt; font-weight: bold; }
        .card-line { font-size: 7pt; margin-top: 1mm; }
        .card-label { color: #94a3b8; text-transform: uppercase; font-size: 6pt; }
        .card-footer { font-size: 5.5pt; color: #94a3b8; text-align: center; padding: 1mm 2mm; border-top: 1px solid #f1f5f9; }
        .back-body { padding: 3mm 3mm 1mm; text-align: center; }
        .back-code { font-family: DejaVu Sans Mono, monospace; letter-spacing: 2px; font-size: 8pt; font-weight: bold; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1.5mm 2mm; display: inline-block; }
        .back-terms { font-size: 6pt; color: #475569; margin-top: 2mm; line-height: 1.4; }
        table.sig-table { width: 100%; border-collapse: collapse; margin-top: 3mm; }
        table.sig-table td { width: 50%; border-top: 1px solid #94a3b8; padding-top: 1mm; font-size: 5.5pt; text-transform: uppercase; color: #94a3b8; text-align: center; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
@php $chunks = array_chunk($cards, 8); @endphp

@foreach($chunks as $i => $chunk)
<div @if($i > 0) class="page-break" @endif>
    <div class="section-title">Student ID cards — Fronts (page {{ $i + 1 }} of {{ count($chunks) }})</div>
    <table class="grid">
        @foreach(array_chunk($chunk, 2) as $row)
        <tr>
            @foreach($row as $card)
            @php $s = $card['student']; @endphp
            <td>
                <div class="card-box">
                    <div class="card-band">
                        <table class="band-table"><tr>
                            <td class="band-logo">@if($emblemUri)<img src="{{ $emblemUri }}">@endif</td>
                            <td class="band-name">{{ config('college.institution_name', config('app.name')) }}</td>
                            <td class="band-logo right">@if($logoUri)<img src="{{ $logoUri }}">@endif</td>
                        </tr></table>
                    </div>
                    <div class="card-body">
                        <table class="body-table"><tr>
                            <td class="photo-cell"><img src="{{ $card['photoUri'] }}"></td>
                            <td class="info-cell">
                                <div class="card-name">{{ $s->full_name }}</div>
                                <div class="card-line"><span class="card-label">Programme</span> {{ $s->programme->code ?? '—' }}</div>
                                <div class="card-line"><span class="card-label">Reg. No</span> {{ $s->nactvet_reg_no ?: $s->reg_no }}</div>
                                @php
                                    $intakeYear = $s->intake_year ?? \App\Support\AcademicSession::defaultStartYear();
                                @endphp
                                <div class="card-line"><span class="card-label">Valid</span> {{ $intakeYear }} – {{ $intakeYear + 3 }}</div>
                            </td>
                        </tr></table>
                    </div>
                    <div class="card-footer">If found, return to {{ config('college.institution_name', config('app.name')) }} registrar's office.</div>
                </div>
            </td>
            @endforeach
            @if(count($row) === 1)<td></td>@endif
        </tr>
        @endforeach
    </table>
</div>
@endforeach

@foreach($chunks as $i => $chunk)
<div class="page-break">
    <div class="section-title">Student ID cards — Backs (page {{ $i + 1 }} of {{ count($chunks) }})</div>
    <table class="grid">
        @foreach(array_chunk($chunk, 2) as $row)
        <tr>
            @foreach($row as $card)
            @php $s = $card['student']; @endphp
            <td>
                <div class="card-box">
                    <div class="card-band">
                        <table class="band-table"><tr>
                            <td class="band-logo">@if($emblemUri)<img src="{{ $emblemUri }}">@endif</td>
                            <td class="band-name">{{ config('college.institution_name', config('app.name')) }}</td>
                            <td class="band-logo right">@if($logoUri)<img src="{{ $logoUri }}">@endif</td>
                        </tr></table>
                    </div>
                    <div class="back-body">
                        <span class="back-code">{{ $s->nactvet_reg_no ?: $s->reg_no }}</span>
                        <div class="back-terms">
                            This card is the property of <strong>{{ config('college.institution_name', config('app.name')) }}</strong> and must be surrendered on request.
                            Non-transferable; carry at all times on campus. Report loss to the registrar's office immediately.
                        </div>
                        <table class="sig-table"><tr>
                            <td>Holder's signature</td>
                            <td>Principal</td>
                        </tr></table>
                    </div>
                </div>
            </td>
            @endforeach
            @if(count($row) === 1)<td></td>@endif
        </tr>
        @endforeach
    </table>
</div>
@endforeach
</body>
</html>
