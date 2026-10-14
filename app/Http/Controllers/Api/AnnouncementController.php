<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

/**
 * Mirrors api/announcements.php.
 */
class AnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $announcements = Announcement::query()
                ->where('status', Announcement::STATUS_PUBLISHED)
                ->orderByDesc('publish_date')
                ->orderByDesc('created_at')
                ->get(['id', 'title', 'message', 'publish_date', 'status']);

            return response()->json([
                'success' => true,
                'announcements' => $announcements,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Server error while loading announcements.',
            ], 500);
        }
    }
}
