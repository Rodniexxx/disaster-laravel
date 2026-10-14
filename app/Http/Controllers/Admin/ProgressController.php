<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlayerProgress;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $rows = PlayerProgress::query()
            ->with('user')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhere('scenario_slug', 'like', "%{$search}%");
            }))
            ->when(in_array($status, ['not_started', 'in_progress', 'completed'], true),
                fn ($q) => $q->where('status', $status))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.progress.index', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
        ]);
    }
}
