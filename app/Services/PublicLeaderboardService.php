<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Read-only projection. Ordering exactly matches ReadinessService::leaderboard.
class PublicLeaderboardService
{
    public function scores(string $category)
    {
        $query = DB::table('readiness_progress as p')->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.completed', true)->where('u.is_active', true);
        if ($category !== 'Overall') $query->where('p.scenario', $category);
        return $query->select('u.id', 'u.username')->selectRaw('SUM(p.best_overall_score) as score')
            ->groupBy('u.id', 'u.username');
    }

    public function rank(int $userId, string $category): ?object
    {
        $row = DB::query()->fromSub($this->scores($category), 'scores')->where('id', $userId)->first();
        if (!$row) return null;
        $row->rank = 1 + DB::query()->fromSub($this->scores($category), 'scores')
            ->where(fn ($q) => $q->where('score', '>', $row->score)
                ->orWhere(fn ($tie) => $tie->where('score', $row->score)->where('id', '<', $userId)))->count();
        return $row;
    }

    private function entry(object $row, int $rank, string $category, int $viewer): array
    {
        // An opaque, expiring locator, bound to the viewing account; never an account ID or credential.
        $reference = Crypt::encryptString(json_encode(['subject' => (int) $row->id, 'viewer' => $viewer, 'expires' => now()->addMinutes(15)->timestamp]));
        return ['rank' => $rank, 'username' => $row->username, 'category' => $category, 'score' => (int) $row->score,
            'is_current_player' => (int) $row->id === $viewer, 'profile_ref' => $reference];
    }

    public function board(string $category, int $viewer): array
    {
        $rows = $this->scores($category)->orderByDesc('score')->orderBy('u.id')->limit(100)->get();
        $entries = $rows->values()->map(fn ($r, $i) => $this->entry($r, $i + 1, $category, $viewer))->all();
        $own = collect($entries)->firstWhere('is_current_player', true);
        if (!$own && ($row = $this->rank($viewer, $category))) $own = $this->entry($row, $row->rank, $category, $viewer);
        return ['leaderboard' => $entries, 'current_player' => $own];
    }

    public function resolve(string $reference, int $viewer): User
    {
        try { $data = json_decode(Crypt::decryptString($reference), true, 8, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { abort(404); }
        abort_unless(is_array($data) && ($data['viewer'] ?? null) === $viewer
            && is_int($data['subject'] ?? null) && ($data['expires'] ?? 0) > now()->timestamp, 404);
        return User::query()->whereKey($data['subject'])->where('is_active', true)->firstOrFail();
    }
}
