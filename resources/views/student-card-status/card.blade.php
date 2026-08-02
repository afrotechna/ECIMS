<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student ID Card - {{ $student->full_name }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --id-navy-1: #071d52;
            --id-navy-2: #1a4fb5;
            --id-gold: #d4af37;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #eef1f6;
            margin: 0;
            padding: 2.5rem 1rem;
        }
        .id-card {
            width: 520px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 34px rgba(7, 29, 82, .22);
            position: relative;
        }
        .id-band {
            background: linear-gradient(135deg, var(--id-navy-1) 0%, var(--id-navy-2) 100%);
            color: #fff;
            padding: .6rem 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            position: relative;
        }
        .id-band::after {
            content: '';
            position: absolute; left: 0; right: 0; bottom: -6px; height: 6px;
            background: linear-gradient(90deg, var(--id-gold) 0%, #f4e5b0 50%, var(--id-gold) 100%);
        }
        .id-band-brand { display: flex; align-items: center; gap: .6rem; min-width: 0; }
        .id-band img { height: 30px; width: 30px; object-fit: contain; flex-shrink: 0; }
        .id-band h1 { margin: 0; font-size: .78rem; font-weight: 800; letter-spacing: .01em; text-transform: uppercase; line-height: 1.2; }
        .id-band .sub { font-size: .58rem; letter-spacing: .1em; text-transform: uppercase; opacity: .85; }
        .id-band-tag {
            font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
            background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.35);
            border-radius: 999px; padding: .25rem .7rem; white-space: nowrap;
        }
        .id-body { padding: 1.1rem 1.25rem .4rem; position: relative; display: flex; gap: 1.1rem; }
        .id-photo-col { flex-shrink: 0; width: 116px; text-align: center; }
        .id-photo {
            width: 116px; height: 136px; border-radius: 10px; object-fit: cover;
            border: 3px solid #fff; box-shadow: 0 0 0 1px #e2e8f0, 0 4px 10px rgba(10,22,40,.12);
            background: #f1f5f9; display: block;
        }
        .id-photo-fallback {
            width: 116px; height: 136px; border-radius: 10px;
            background: linear-gradient(135deg, var(--id-navy-1), var(--id-navy-2)); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.1rem; font-weight: 800; box-shadow: 0 0 0 1px #e2e8f0, 0 4px 10px rgba(10,22,40,.12);
        }
        .id-barcode { margin-top: .55rem; }
        .id-barcode .bars { height: 20px; background: repeating-linear-gradient(90deg, #0f172a 0 2px, transparent 2px 4px); opacity: .85; }
        .id-barcode .code { font-family: 'Courier New', monospace; letter-spacing: .18em; font-size: .68rem; font-weight: 700; color: #0f172a; margin-top: .2rem; }
        .id-info-col { flex: 1; min-width: 0; }
        .id-name { font-size: 1.15rem; font-weight: 800; color: #0f172a; line-height: 1.15; margin-bottom: .15rem; }
        .id-role { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; color: var(--id-navy-2); margin-bottom: .6rem; }
        .id-divider { border: none; border-top: 1px dashed #e2e8f0; margin: .5rem 0; }
        .id-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem .9rem; }
        .id-grid .label { font-size: .6rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; font-weight: 700; margin-bottom: .1rem; }
        .id-grid .value { font-size: .84rem; font-weight: 700; color: #0f172a; }
        .id-footer {
            padding: .5rem 1.25rem .8rem; text-align: center; font-size: .58rem; color: #94a3b8;
            border-top: 1px solid #f1f5f9; letter-spacing: .02em; margin-top: .5rem;
        }
        .id-footer strong { color: #64748b; }
        .id-watermark {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            pointer-events: none; opacity: .04; font-size: 6.5rem; font-weight: 900; color: #071d52;
            transform: rotate(-18deg); overflow: hidden; z-index: 0;
        }
        .id-body > * { position: relative; z-index: 1; }
        .id-actions { max-width: 520px; margin: 1.25rem auto 0; text-align: center; }
        @media print {
            body { background: #fff; padding: 0; }
            .id-card { box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="id-band">
            <div class="id-band-brand">
                @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="">
                @endif
                <div>
                    <h1>{{ config('college.institution_name', config('app.name')) }}</h1>
                    <div class="sub">Student Identity Card</div>
                </div>
            </div>
            <span class="id-band-tag">{{ \App\Support\AcademicSession::label(\App\Support\AcademicSession::defaultStartYear()) }}</span>
        </div>
        <div class="id-body">
            <div class="id-watermark">ID</div>
            <div class="id-photo-col">
                @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="" class="id-photo">
                @else
                <div class="id-photo-fallback">{{ strtoupper(substr($student->first_name ?? '?', 0, 1).substr($student->last_name ?? '', 0, 1)) }}</div>
                @endif
                <div class="id-barcode">
                    <div class="bars"></div>
                    <div class="code">{{ $student->reg_no }}</div>
                </div>
            </div>
            <div class="id-info-col">
                <div class="id-name">{{ $student->full_name }}</div>
                <div class="id-role">{{ $student->programme->name ?? 'Student' }}</div>
                <hr class="id-divider">
                <div class="id-grid">
                    <div>
                        <div class="label">Reg. No</div>
                        <div class="value">{{ $student->reg_no }}</div>
                    </div>
                    <div>
                        <div class="label">Programme</div>
                        <div class="value">{{ $student->programme->code ?? '—' }}</div>
                    </div>
                    @if($student->nactvet_reg_no)
                    <div>
                        <div class="label">NACTVET</div>
                        <div class="value">{{ $student->nactvet_reg_no }}</div>
                    </div>
                    @endif
                    <div>
                        <div class="label">NTA Level</div>
                        <div class="value">{{ $student->nta_level ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Intake year</div>
                        <div class="value">{{ $student->intake_year ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Valid through</div>
                        <div class="value">{{ \App\Support\AcademicSession::defaultStartYear() + 1 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="id-footer">
            If found, please return to <strong>{{ config('college.institution_name', config('app.name')) }}</strong> registrar's office.
        </div>
    </div>
    <div class="id-actions no-print">
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print ID card</button>
    </div>
</body>
</html>
