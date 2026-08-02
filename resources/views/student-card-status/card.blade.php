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
        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #eef1f6;
            margin: 0;
            padding: 2.5rem 1rem;
            overflow-x: hidden;
        }
        @media (max-width: 560px) {
            body { padding: 1.5rem .6rem; }
        }
        .id-card-scene {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            perspective: 1800px;
        }
        .id-card-flipper {
            position: relative;
            width: 100%;
            transition: transform .7s cubic-bezier(.4, .1, .2, 1);
            transform-style: preserve-3d;
            cursor: pointer;
        }
        .id-card-flipper.flipped { transform: rotateY(180deg); }
        .id-card {
            width: 100%;
            background: linear-gradient(160deg, #ffffff 0%, #f3f6fc 55%, #e9eef8 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 34px rgba(7, 29, 82, .22);
            position: relative;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }
        .id-card-back {
            position: absolute;
            inset: 0;
            transform: rotateY(180deg);
        }
        .id-band {
            background: linear-gradient(135deg, var(--id-navy-1) 0%, var(--id-navy-2) 100%);
            color: #fff;
            padding: .7rem 1.1rem;
            display: flex;
            align-items: center;
            gap: .6rem;
            position: relative;
        }
        .id-band::after {
            content: '';
            position: absolute; left: 0; right: 0; bottom: -6px; height: 6px;
            background: linear-gradient(90deg, var(--id-gold) 0%, #f4e5b0 50%, var(--id-gold) 100%);
        }
        .id-band-side { flex-shrink: 0; display: flex; align-items: center; }
        .id-band-center { flex: 1; min-width: 0; text-align: center; }
        .id-band img { height: 46px; width: 46px; object-fit: contain; flex-shrink: 0; background: #fff; border-radius: 50%; padding: 3px; }
        .id-band h1 { margin: 0; font-size: .78rem; font-weight: 800; letter-spacing: .01em; text-transform: uppercase; line-height: 1.2; }
        .id-band .sub { font-size: .58rem; letter-spacing: .1em; text-transform: uppercase; opacity: .85; }
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
            padding: .55rem 1.5rem 1.4rem; text-align: center; font-size: .62rem; color: #94a3b8;
            border-top: 1px solid #f1f5f9; letter-spacing: .01em; line-height: 1.5; margin-top: .5rem;
            overflow-wrap: break-word; word-break: break-word;
        }
        .id-footer strong { color: #64748b; }
        .id-back-body { padding: 1.4rem 1.4rem 1rem; position: relative; text-align: center; }
        .id-back-barcode, .id-back-terms, .id-back-signature { position: relative; z-index: 1; }
        .id-back-barcode .bars {
            height: 42px;
            background: repeating-linear-gradient(90deg, #0f172a 0 3px, transparent 3px 6px);
            opacity: .85; border-radius: 3px;
        }
        .id-back-barcode .code {
            font-family: 'Courier New', monospace; letter-spacing: .3em; font-size: .8rem;
            font-weight: 700; color: #0f172a; margin-top: .35rem;
        }
        .id-back-terms {
            margin-top: 1.1rem; font-size: .68rem; line-height: 1.5; color: #475569;
            border-top: 1px dashed #e2e8f0; padding-top: .8rem;
        }
        .id-back-terms strong { color: #0f172a; }
        .id-back-signature {
            margin-top: 1.3rem; display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem;
        }
        .id-back-signature .line {
            flex: 1; border-top: 1px solid #94a3b8; padding-top: .3rem;
            font-size: .58rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; font-weight: 700;
        }
        .id-watermark {
            position: absolute; inset: -40px; pointer-events: none; z-index: 0; overflow: hidden;
            background-image: url('{{ asset('images/logo.png') }}');
            background-repeat: repeat;
            background-size: 68px 68px;
            opacity: .06;
            transform: rotate(-20deg);
        }
        .id-photo-col, .id-info-col { position: relative; z-index: 1; }
        .id-flip-hint {
            max-width: 520px; margin: .9rem auto 0; text-align: center;
            font-size: .78rem; color: #64748b;
        }
        .id-actions { max-width: 520px; margin: .6rem auto 0; text-align: center; display: flex; justify-content: center; gap: .5rem; }
        @media print {
            body { background: #fff; padding: 0; }
            .id-card-scene { perspective: none; }
            .id-card-flipper, .id-card-flipper.flipped { transform: none !important; transition: none !important; }
            .id-card { position: relative !important; backface-visibility: visible !important; margin-bottom: 14px; }
            .id-card-back { position: relative !important; inset: auto !important; transform: none !important; page-break-before: always; }
            .id-card, .id-band, .id-watermark, .id-photo-fallback, .id-barcode .bars, .id-back-barcode .bars {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
            }
            .id-card { box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="id-card-scene">
        <div class="id-card-flipper" id="idCardFlipper">
            <div class="id-card">
                <div class="id-band">
                    <div class="id-band-side">
                        @if(file_exists(public_path('images/national-emblem.png')))
                        <img src="{{ asset('images/national-emblem.png') }}" alt="">
                        @endif
                    </div>
                    <div class="id-band-center">
                        <h1>{{ config('college.institution_name', config('app.name')) }}</h1>
                        <div class="sub">Student Identity Card</div>
                    </div>
                    <div class="id-band-side">
                        @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="">
                        @endif
                    </div>
                </div>
                <div class="id-body">
                    @if(file_exists(public_path('images/logo.png')))
                    <div class="id-watermark"></div>
                    @endif
                    <div class="id-photo-col">
                        @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="" class="id-photo">
                        @else
                        <div class="id-photo-fallback">{{ strtoupper(substr($student->first_name ?? '?', 0, 1).substr($student->last_name ?? '', 0, 1)) }}</div>
                        @endif
                        <div class="id-barcode">
                            <div class="bars"></div>
                            <div class="code">{{ $student->nactvet_reg_no ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="id-info-col">
                        <div class="id-name">{{ $student->full_name }}</div>
                        <div class="id-role">{{ $student->programme->name ?? 'Student' }}</div>
                        <hr class="id-divider">
                        @php
                            $intakeYear = $student->intake_year ?? \App\Support\AcademicSession::defaultStartYear();
                            $validFrom = \Carbon\Carbon::create($intakeYear, 10, 1);
                            $validTo = \Carbon\Carbon::create($intakeYear + 3, 10, 1);
                        @endphp
                        <div class="id-grid">
                            <div>
                                <div class="label">NACTVET Reg. No</div>
                                <div class="value">{{ $student->nactvet_reg_no ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="label">Programme</div>
                                <div class="value">{{ $student->programme->code ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="label">Intake year</div>
                                <div class="value">{{ $student->intake_year ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="label">Valid</div>
                                <div class="value">{{ $validFrom->format('M Y') }} – {{ $validTo->format('M Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="id-footer">
                    If found, please return to <strong>{{ config('college.institution_name', config('app.name')) }}</strong> registrar's office.
                </div>
            </div>
            <div class="id-card id-card-back">
                <div class="id-band">
                    <div class="id-band-side">
                        @if(file_exists(public_path('images/national-emblem.png')))
                        <img src="{{ asset('images/national-emblem.png') }}" alt="">
                        @endif
                    </div>
                    <div class="id-band-center">
                        <h1>{{ config('college.institution_name', config('app.name')) }}</h1>
                        <div class="sub">Student Identity Card</div>
                    </div>
                    <div class="id-band-side">
                        @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="">
                        @endif
                    </div>
                </div>
                <div class="id-back-body">
                    @if(file_exists(public_path('images/logo.png')))
                    <div class="id-watermark"></div>
                    @endif
                    <div class="id-back-barcode">
                        <div class="bars"></div>
                        <div class="code">{{ $student->nactvet_reg_no ?: '—' }}</div>
                    </div>
                    <div class="id-back-terms">
                        This card is the property of <strong>{{ config('college.institution_name', config('app.name')) }}</strong> and must be
                        surrendered on request. It is non-transferable and must be carried at all times while on campus.
                        Report loss immediately to the registrar's office.
                    </div>
                    <div class="id-back-signature">
                        <div class="line">Holder's signature</div>
                        <div class="line">Principal</div>
                    </div>
                </div>
                <div class="id-footer">
                    If found, please return to <strong>{{ config('college.institution_name', config('app.name')) }}</strong> registrar's office.
                </div>
            </div>
        </div>
    </div>
    <div class="id-flip-hint no-print"><i class="bi bi-hand-index-thumb me-1"></i>Tap the card to flip it over</div>
    <div class="id-actions no-print">
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print ID card</button>
    </div>
    <script>
        document.getElementById('idCardFlipper').addEventListener('click', function () {
            this.classList.toggle('flipped');
        });
    </script>
</body>
</html>
