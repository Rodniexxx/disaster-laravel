<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CompletionCertificateService;
use App\Services\PlayerStatsService;
use App\Support\DisasterCatalog;
use Illuminate\Http\JsonResponse;

class PublicPlayerProfileController extends Controller
{
    public function __invoke(
        User $user,
        PlayerStatsService $players,
        CompletionCertificateService $certificates,
        DisasterCatalog $catalog,
    ): JsonResponse {
        abort_unless($user->isActive(), 404);

        $stats = $players->forUser($user);
        // status() is read-only: viewing another player must never issue a certificate.
        $certificate = $certificates->status($user);
        $stars = collect($certificate['scenarios'])->keyBy('scenario');
        $legacy = $user->progress()->get(['scenario_slug', 'status'])
            ->groupBy(fn ($row) => $catalog->resolveCategory($row->scenario_slug) ?? '');

        $scenarios = $stats['scenarios']->map(function ($scenario) use ($stats, $stars, $legacy) {
            $status = $stats['status'][$scenario->slug];
            $saved = $legacy->get($scenario->category, collect());
            // Honor explicit legacy completion/start records, without using legacy
            // scores to change the readiness personal best, stars, or ranking.
            if ($saved->contains('status', 'completed')) {
                $status = 'completed';
            } elseif ($status === 'not_started' && $saved->contains('status', 'in_progress')) {
                $status = 'in_progress';
            }

            return [
                'name' => $scenario->title,
                'best_score' => $stats['hasScore'][$scenario->slug] ? $stats['perCategory'][$scenario->slug] : null,
                'status' => $status,
                'status_label' => PlayerStatsService::statusLabel($status),
                'stars' => $stars->get($scenario->category)['stars'] ?? 0,
            ];
        })->values();
        $completed = $scenarios->where('status', 'completed')->count();

        // Explicit allow-list. Never serialize the User, profile, or certificate
        // models (emails, account details, IDs, and storage paths stay private).
        return response()->json([
            'username' => $user->username,
            'avatar_url' => $user->profile_picture_url,
            'joined' => $user->created_at?->format('F j, Y'),
            'rank' => $stats['rank'],
            'total_score' => $stats['overall'],
            'max_score' => $stats['overallMax'],
            'completed_scenarios' => $completed,
            'total_scenarios' => $scenarios->count(),
            'progress_percent' => (int) round($completed / max(1, $scenarios->count()) * 100),
            'stars' => $certificate['total_stars'],
            'max_stars' => $certificate['max_stars'],
            'perfect_scores' => $scenarios->whereStrict('best_score', 100)->count(),
            'scenarios' => $scenarios,
            'achievements' => collect($certificate['achievements'])->where('unlocked', true)->pluck('name')->values(),
            'certificate_status' => $certificate['issued_at'] !== null
                ? 'Issued' : ($certificate['eligible'] ? 'Eligible, not issued' : 'Not earned'),
        ])->header('Cache-Control', 'no-store');
    }
}
