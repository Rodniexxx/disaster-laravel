<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Writes to admin_activity_logs, matching the legacy logAdminActivity() helper.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function log(?User $admin, string $action, string $resource, array $details = []): void
    {
        $admin ??= Auth::user();

        if (! $admin || ! $admin->isAdmin()) {
            return;
        }

        AdminActivityLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'resource' => $resource,
            'details' => $details,
        ]);
    }
}
