<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CompletionCertificatePdf;
use App\Services\CompletionCertificateService;
use Illuminate\Http\Request;

class CompletionCertificateController extends Controller
{
    public function __construct(
        private readonly CompletionCertificateService $certificates,
        private readonly CompletionCertificatePdf $pdf,
    ) {}

    public function show(Request $request)
    {
        $status = $this->certificates->status($request->user());
        return response()->json([
            'success' => true,
            'eligibility' => $status,
            'certificate' => $status['eligible'] && $status['certificate_id'] ? $status : null,
        ]);
    }

    public function issue(Request $request)
    {
        abort_if($request->all() !== [], 422, 'Certificate issuance does not accept player-supplied data.');
        $status = $this->certificates->status($request->user());
        if (! $status['eligible']) {
            return response()->json([
                'success' => false,
                'message' => 'Complete all four scenarios and unlock all five achievements first.',
                'eligibility' => $status,
                'certificate' => null,
            ], 403);
        }
        $issued = $this->certificates->issue($request->user());
        if (! $issued) {
            return response()->json(['success' => false, 'message' => 'Saved completion requirements changed. Reload and retry.'], 403);
        }
        return response()->json(['success' => true, 'eligibility' => $issued, 'certificate' => $issued]);
    }

    public function pdf(Request $request)
    {
        $certificate = $this->certificates->status($request->user());
        abort_unless($certificate['eligible'] && $certificate['certificate_id'], 403, 'Certificate requirements are not complete.');
        return $this->download($certificate);
    }

    private function download(array $certificate)
    {
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
