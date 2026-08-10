<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Card verification — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        :root {
            --navy-1: #071d52;
            --navy-2: #1a4fb5;
            --green: #16a34a;
            --red: #dc2626;
            --amber: #d97706;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(160deg, var(--navy-1) 0%, var(--navy-2) 100%);
            min-height: 100vh;
            margin: 0;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verify-card {
            width: 100%;
            max-width: 380px;
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,.35);
        }
        .verify-brand {
            padding: 1.1rem 1.25rem;
            text-align: center;
            border-bottom: 1px solid #eef1f6;
        }
        .verify-brand img { height: 44px; width: 44px; object-fit: contain; border-radius: 50%; margin-bottom: .4rem; }
        .verify-brand .name { font-size: .85rem; font-weight: 800; color: var(--navy-1); text-transform: uppercase; letter-spacing: .02em; }
        .verify-brand .sub { font-size: .68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-top: .1rem; }

        .verify-banner {
            padding: 1.1rem 1.25rem;
            text-align: center;
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: .02em;
        }
        .verify-banner.is-valid { background: linear-gradient(135deg, var(--green) 0%, #15803d 100%); }
        .verify-banner.is-invalid { background: linear-gradient(135deg, var(--red) 0%, #b91c1c 100%); }
        .verify-banner.is-amber { background: linear-gradient(135deg, var(--amber) 0%, #b45309 100%); }
        .verify-banner .icon { font-size: 1.6rem; display: block; margin-bottom: .3rem; }
        .verify-banner .reason { font-size: .72rem; font-weight: 500; opacity: .92; margin-top: .25rem; text-transform: none; letter-spacing: normal; }

        .verify-body { padding: 1.4rem 1.25rem; text-align: center; }
        .verify-photo {
            width: 108px; height: 128px; object-fit: cover; border-radius: 10px;
            border: 3px solid #fff; box-shadow: 0 0 0 1px #e2e8f0, 0 4px 10px rgba(10,22,40,.12);
            margin: 0 auto .9rem; display: block;
        }
        .verify-photo-fallback {
            width: 108px; height: 128px; border-radius: 10px;
            background: linear-gradient(135deg, var(--navy-1), var(--navy-2)); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.9rem; font-weight: 800; margin: 0 auto .9rem;
        }
        .verify-name { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: .15rem; }
        .verify-programme { font-size: .82rem; color: var(--navy-2); font-weight: 700; margin-bottom: 1rem; }
        .verify-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem 1rem; text-align: left; }
        .verify-grid .label { font-size: .58rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; font-weight: 700; margin-bottom: .12rem; }
        .verify-grid .value { font-size: .85rem; font-weight: 700; color: #0f172a; }

        .verify-footer {
            padding: .9rem 1.25rem 1.2rem; text-align: center; font-size: .64rem; color: #94a3b8;
            border-top: 1px solid #f1f5f9; line-height: 1.5;
        }
        .verify-not-found { padding: 2rem 1.25rem; text-align: center; }
        .verify-not-found .icon { font-size: 2.4rem; color: var(--red); margin-bottom: .6rem; }
        .verify-not-found h2 { font-size: 1.05rem; color: #0f172a; margin: 0 0 .4rem; }
        .verify-not-found p { font-size: .82rem; color: #64748b; margin: 0; }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="verify-brand">
            @if(file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="">
            @endif
            <div class="name">{{ config('college.institution_name', config('app.name')) }}</div>
            <div class="sub">Student ID Verification</div>
        </div>

        @if(! $student)
            <div class="verify-not-found">
                <div class="icon">&#9888;</div>
                <h2>Not a valid student card</h2>
                <p>This code doesn't match any {{ config('app.name') }} student ID card. It may be damaged, expired, or fraudulent.</p>
            </div>
        @else
            @php
                $statusLabels = ['active' => 'Active student', 'graduated' => 'Graduated', 'withdrawn' => 'Withdrawn', 'deactivated' => 'Deactivated'];
                $cardStatusLabel = $student->cardStatuses->firstWhere('document_type', 'student_id')?->statusLabel() ?? 'Not yet issued';
                $reason = null;
                if ($isValid) {
                    $bannerClass = 'is-valid';
                    $bannerIcon = '&#10003;';
                    $bannerText = 'VALID';
                } elseif ($student->status !== 'active') {
                    $bannerClass = 'is-invalid';
                    $bannerIcon = '&#10007;';
                    $bannerText = 'NOT A CURRENT STUDENT';
                    $reason = ($statusLabels[$student->status] ?? ucfirst($student->status)).' — this card should not be honoured.';
                } else {
                    $bannerClass = 'is-amber';
                    $bannerIcon = '&#9888;';
                    $bannerText = 'CARD NOT YET ISSUED';
                    $reason = 'This student is active but the registrar has not printed/activated their card yet.';
                }
            @endphp
            <div class="verify-banner {{ $bannerClass }}">
                <span class="icon">{!! $bannerIcon !!}</span>
                {{ $bannerText }}
                @if($reason)<div class="reason">{{ $reason }}</div>@endif
            </div>
            <div class="verify-body">
                @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="" class="verify-photo">
                @else
                <div class="verify-photo-fallback">{{ strtoupper(substr($student->first_name ?? '?', 0, 1).substr($student->last_name ?? '', 0, 1)) }}</div>
                @endif
                <div class="verify-name">{{ $student->full_name }}</div>
                <div class="verify-programme">{{ $student->programme->name ?? '—' }}</div>
                <div class="verify-grid">
                    <div>
                        <div class="label">NACTVET Reg. No</div>
                        <div class="value">{{ $student->nactvet_reg_no ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Programme</div>
                        <div class="value">{{ $student->programme->code ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Student status</div>
                        <div class="value">{{ $statusLabels[$student->status] ?? ucfirst($student->status) }}</div>
                    </div>
                    <div>
                        <div class="label">Card status</div>
                        <div class="value">{{ $cardStatusLabel }}</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="verify-footer">
            Verified via {{ config('app.name') }} &middot; {{ now()->format('d M Y, H:i') }}<br>
            If in doubt, contact the registrar's office directly.
        </div>
    </div>
</body>
</html>
