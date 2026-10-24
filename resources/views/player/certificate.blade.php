@extends('layouts.app')

@section('title', 'Certificate of Completion')

@section('content')
    <section class="certificate-page" aria-labelledby="certificate-page-title">
        <div class="certificate-toolbar">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">BACK TO DASHBOARD</a>
            <a href="{{ route('dashboard.certificate.pdf') }}" class="btn btn-primary">DOWNLOAD PDF</a>
        </div>

        <h1 id="certificate-page-title" class="visually-hidden">Certificate of Completion</h1>
        <div class="certificate-sheet" aria-label="MANGNO certificate of completion for {{ $certificate['display_name'] }}">
            <img src="{{ asset('images/MANGNO_Certificate_Template.jpg') }}" alt="" class="certificate-artwork">
            <div class="certificate-player-name">{{ $certificate['display_name'] }}</div>
            <div class="certificate-score-ribbon">
                <strong>{{ $certificate['perfect_score'] ? 'PERFECT SCORE' : 'TOTAL STARS' }}</strong>
                <span>{{ $certificate['total_stars'] }}/12 STARS</span>
            </div>
            <div class="certificate-earned-stars" aria-label="{{ $certificate['total_stars'] }} of 12 stars earned">
                @for ($star = 0; $star < 12; $star++)
                    <span class="{{ $star < $certificate['total_stars'] ? 'earned' : 'pending' }}">★</span>
                @endfor
            </div>
            <div class="certificate-issue-date">{{ $certificate['issue_date'] }}</div>
            <div class="certificate-id">CERTIFICATE ID&nbsp; {{ $certificate['certificate_id'] }}</div>
        </div>
    </section>

    <style>
        .certificate-page { max-width: 1500px; margin: 0 auto; padding: clamp(12px, 2vw, 28px); }
        .certificate-toolbar { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .certificate-sheet { position: relative; width: min(100%, 1200px); aspect-ratio: 3 / 2; margin: 0 auto; overflow: hidden; box-shadow: 0 18px 55px rgba(3, 17, 31, .24); }
        .certificate-artwork { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: fill; }
        .certificate-player-name { position: absolute; left: 23%; top: 34%; width: 54%; height: 11.3%; display: flex; align-items: center; justify-content: center; padding: 0 1%; background: #f6f0e3; color: #091e35; font: italic 600 clamp(18px, 3.4vw, 52px)/1.05 Georgia, serif; text-align: center; overflow-wrap: anywhere; }
        .certificate-player-name::after { position: absolute; right: 7%; bottom: 10%; left: 7%; height: 2px; background: #b3750d; content: ""; }
        .certificate-score-ribbon { position: absolute; left: 78.7%; top: 25.8%; width: 17.1%; height: 11%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2%; padding: 0 1%; background: #e8ad1e; clip-path: polygon(20% 0, 78% 0, 100% 50%, 78% 100%, 20% 100%, 0 50%); color: #071c32; font: 700 clamp(8px, 1.1vw, 16px)/1.05 Arial, sans-serif; text-align: center; }
        .certificate-score-ribbon span { font-size: 1.06em; }
        .certificate-earned-stars { position: absolute; left: 28%; top: 64.8%; width: 44%; height: 7%; display: flex; align-items: center; justify-content: space-evenly; padding: 0 1%; background: #03172d; font: 700 clamp(10px, 1.7vw, 23px)/1 Arial, sans-serif; }
        .certificate-earned-stars span { color: #64748b; text-shadow: none; }
        .certificate-earned-stars span.earned { color: #ffd700; text-shadow: 0 0 5px rgba(255, 215, 0, .34); }
        .certificate-issue-date { position: absolute; left: 24%; top: 87.4%; width: 18%; height: 5%; display: flex; align-items: center; justify-content: center; background: #f6f0e3; color: #15283c; font: 500 clamp(8px, 1.12vw, 15px)/1.1 Arial, sans-serif; text-align: center; }
        .certificate-id { position: absolute; left: 37%; bottom: 2.5%; width: 26%; color: #172b40; font: 700 clamp(5px, .6vw, 9px)/1 Arial, sans-serif; text-align: center; }
        @media (max-width: 680px) { .certificate-page { padding: 8px; } .certificate-toolbar .btn { padding: 9px 12px; font-size: .78rem; } }
        @page { size: A4 landscape; margin: 6mm; }
        @media print {
            html, body { background: #fff !important; }
            .site-header, .site-footer, .certificate-toolbar { display: none !important; }
            .certificate-page { width: 100%; max-width: none; margin: 0; padding: 0; }
            .certificate-sheet { width: 100%; max-width: none; box-shadow: none; page-break-inside: avoid; }
        }
    </style>
@endsection
