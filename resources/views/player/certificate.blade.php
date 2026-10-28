@extends('layouts.app')
@section('title', 'Certificate of Completion')
@section('content')
    @php
        $stars = min(12, max(0, (int) $certificate['total_stars']));
        $artworkPath = 'images/MANGNO_Certificate_Template.jpg';
        $artworkFile = public_path($artworkPath);
        $artworkHash = is_file($artworkFile) ? @hash_file('sha256', $artworkFile) : false;
        $artwork = asset($artworkPath).($artworkHash ? '?v='.substr($artworkHash, 0, 12) : '');
        $certificatePdf = app(\App\Services\CompletionCertificatePdf::class);
        $displayName = $certificatePdf->uppercaseName($certificate['display_name']);
        $nameLayout = $certificatePdf->nameLayout($displayName);
        $patches = [[350, 630, 1300, 170], [300, 978, 1420, 212], [400, 1200, 400, 85]];
    @endphp
    <section class="certificate-page" aria-labelledby="certificate-page-title">
        <div class="certificate-toolbar">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">BACK TO DASHBOARD</a>
            <a href="{{ route('dashboard.certificate.pdf') }}" class="btn btn-primary">DOWNLOAD PDF</a>
        </div>
        <h1 id="certificate-page-title" class="visually-hidden">Certificate of Completion</h1>
        <div class="certificate-sheet" aria-label="MANGNO certificate of completion for {{ $displayName }}">
            <img src="{{ $artwork }}" alt="" class="certificate-artwork">
            <svg class="certificate-fields" viewBox="0 0 1999 1414" role="img" aria-label="{{ $certificate['display_name'] }}, {{ $stars }}/12 stars, issued {{ $certificate['issue_date'] }}">
                @foreach ($patches as [$x, $y, $width, $height])
                    @php($cropWidth = 150 * $width / $height)
                    <svg x="{{ $x }}" y="{{ $y }}" width="{{ $width }}" height="{{ $height }}" viewBox="{{ (1999 - $cropWidth) / 2 }} 480 {{ $cropWidth }} 150" overflow="hidden" aria-hidden="true">
                        <image href="{{ $artwork }}" width="1999" height="1414" />
                    </svg>
                @endforeach
                <g text-anchor="middle" font-family="Helvetica, Arial, sans-serif">
                    <text class="certificate-player-name" text-anchor="middle" x="999.5" font-size="{{ $nameLayout['fontSize'] }}" font-weight="700" fill="#1a1f6b" letter-spacing="{{ $nameLayout['tracking'] }}">
                        @foreach ($nameLayout['lines'] as $lineIndex => $line)
                            <tspan x="999.5" y="{{ $nameLayout['firstBaseline'] + $lineIndex * $nameLayout['leading'] }}">{{ $line }}</tspan>
                        @endforeach
                    </text>
                    <g fill="#1a1f6b" font-size="40" font-style="italic">
                        <text x="999.5" y="1017">The recipient has completed all four disaster scenarios and earned</text>
                        <text x="999.5" y="1069" font-weight="700">{{ $stars }}/12 STARS{{ $stars === 12 ? ' - PERFECT SCORE' : '' }}</text>
                        <text x="999.5" y="1121">with all five preparedness achievements unlocked, demonstrating</text>
                        <text x="999.5" y="1173">knowledge of recommended actions for different disaster scenarios.</text>
                    </g>
                    <text x="600" y="1266" fill="#1a1f6b" font-size="48" font-weight="700">{{ $certificate['issue_date'] }}</text>
                    <text x="999.5" y="1367" fill="#454d5e" font-size="28">CERTIFICATE ID  {{ $certificate['certificate_id'] }}</text>
                </g>
            </svg>
        </div>
    </section>
    <style>
        @font-face { font-family: "Playfair Display"; src: url("{{ asset('fonts/PlayfairDisplay-Bold.ttf') }}") format("truetype"); font-style: normal; font-weight: 700; font-display: block; }
        .certificate-player-name { font-family: "Playfair Display", serif; font-weight: 700; fill: #1a1f6b; }
        .certificate-page { max-width: 1500px; margin: 0 auto; padding: clamp(12px, 2vw, 28px); }
        .certificate-toolbar { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .certificate-sheet { position: relative; width: min(100%, 1200px); aspect-ratio: 1999 / 1414; margin: 0 auto; overflow: hidden; box-shadow: 0 18px 55px rgba(3, 17, 31, .24); }
        .certificate-artwork, .certificate-fields { position: absolute; inset: 0; width: 100%; height: 100%; }
        .certificate-artwork { object-fit: contain; }
        @media (max-width: 680px) { .certificate-page { padding: 8px; } .certificate-toolbar .btn { padding: 9px 12px; font-size: .78rem; } }
        @page { size: A4 landscape; margin: 6mm; }
        @media print {
            html, body { background: #fff !important; margin: 0; padding: 0; }
            .site-header, .site-footer, .certificate-toolbar { display: none !important; }
            #main-content { width: 100%; max-width: none; margin: 0; padding: 0; }
            .certificate-page { width: 100%; max-width: none; margin: 0; padding: 0; }
            .certificate-sheet { width: 279mm; max-width: 100%; box-shadow: none; break-inside: avoid; }
            .certificate-artwork, .certificate-fields { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
@endsection
