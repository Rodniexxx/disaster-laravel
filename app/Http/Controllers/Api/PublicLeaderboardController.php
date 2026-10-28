<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Services\PublicLeaderboardService;
use App\Services\ReadinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PublicLeaderboardController extends Controller
{
    public function __construct(private readonly PublicLeaderboardService $board) {}

    public function index(Request $request)
    {
        $data = $request->validate(['category' => ['sometimes', Rule::in(['Overall', ...array_keys(ReadinessService::CRITERIA)])]]);
        return response()->json(['success' => true, ...$this->board->board($data['category'] ?? 'Overall', $request->user()->id)])
            ->header('Cache-Control', 'private, no-store');
    }

    private function subject(Request $request)
    {
        $data = $request->validate(['profile_ref' => ['required', 'string', 'max:1024']]);
        return $this->board->resolve($data['profile_ref'], $request->user()->id);
    }

    public function show(Request $request)
    {
        $user = $this->subject($request);
        $profile = PlayerProfile::find($user->id);
        $rank = $this->board->rank($user->id, 'Overall');
        $completed = DB::table('readiness_progress')->where('user_id', $user->id)->where('completed', true)
            ->whereIn('scenario', array_keys(ReadinessService::CRITERIA))->orderBy('scenario')->pluck('scenario')->all();
        $badges = ['ready_to_respond', 'storm_ready', 'prepared_learner', 'disaster_ready', 'mangno_master'];
        $earned = DB::table('readiness_achievements')->where('user_id', $user->id)->whereIn('achievement_id', $badges)
            ->orderBy('id')->pluck('achievement_id')->all();
        return response()->json(['success' => true, 'profile' => [
            'display_name' => $profile?->display_name ?? $user->username,
            'has_avatar' => (bool) $profile?->avatar_path,
            'overall_rank' => (int) ($rank?->rank ?? 0), 'overall_score' => (int) ($rank?->score ?? 0),
            'completed_scenarios' => $completed, 'achievements' => $earned,
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function avatar(Request $request)
    {
        $user = $this->subject($request);
        $profile = PlayerProfile::find($user->id);
        abort_unless($profile?->avatar_path, 404);
        try {
            $disk = match ($profile->avatar_disk) {
                's3' => Storage::disk('s3'),
                'profile_volume' => str_starts_with((string) config('profile.volume_root'), '/')
                    ? Storage::build(['driver' => 'local', 'root' => config('profile.volume_root'), 'throw' => true]) : null,
                default => null,
            };
            $bytes = $disk?->get($profile->avatar_path);
        } catch (\Throwable) { abort(503, 'Profile image unavailable.'); }
        abort_unless($bytes && strlen($bytes) <= 262144, 404);
        return response($bytes)->header('Content-Type', 'image/jpeg')->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-store');
    }
}
