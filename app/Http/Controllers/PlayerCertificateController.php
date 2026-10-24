<?php

namespace App\Http\Controllers;

use App\Services\CompletionCertificatePdf;
use App\Services\CompletionCertificateService;
use Illuminate\Http\Request;

class PlayerCertificateController extends Controller
{
    public function __construct(
        private readonly CompletionCertificateService $certificates,
        private readonly CompletionCertificatePdf $pdf,
    ) {}

    public function show(Request $request)
    {
        $certificate = $this->certificates->status($request->user());
        abort_unless($certificate['eligible'] && $certificate['certificate_id'], 403, 'Issue your certificate after completing all requirements.');
        return view('player.certificate', compact('certificate'));
    }

    public function issue(Request $request)
    {
        $certificate = $this->certificates->issue($request->user());
        if (! $certificate) {
            return redirect()->route('dashboard')->with('error', 'Complete all four scenarios and unlock all five achievement badges to earn your certificate.');
        }
        return redirect()->route('dashboard.certificate');
    }

    public function pdf(Request $request)
    {
        $certificate = $this->certificates->status($request->user());
        abort_unless($certificate['eligible'] && $certificate['certificate_id'], 403, 'Certificate requirements are not complete.');
        $bytes = $this->pdf->render($certificate);
        $file = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $certificate['certificate_id']);
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="MANGNO_'.$file.'.pdf"',
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
