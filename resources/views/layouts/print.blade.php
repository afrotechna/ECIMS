<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Print')</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; font-size: 12pt; color: #111; margin: 1.25rem; line-height: 1.35; }
        h1 { font-size: 1.35rem; margin: 0 0 0.35rem; }
        h2 { font-size: 1.05rem; margin: 0 0 0.25rem; }
        .meta { font-size: 0.95rem; margin-bottom: 1rem; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.35rem; }
        th, td { border: 1px solid #222; padding: 0.35rem 0.45rem; text-align: left; vertical-align: top; }
        th { background: #eee; font-size: 0.9rem; }
        .group-block { page-break-inside: avoid; margin-bottom: 1.25rem; }
        .group-head { margin: 1rem 0 0.35rem; padding-bottom: 0.15rem; border-bottom: 2px solid #222; }
        .sn { width: 2.25rem; text-align: center; }
        .no-print { margin-bottom: 1rem; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0.4cm; }
        }
    </style>
    @stack('styles')
</head>
<body>
@yield('content')
</body>
</html>
