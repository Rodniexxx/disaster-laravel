<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;

class LogController extends Controller
{
    public function index()
    {
        return view('admin.logs.index', [
            'logs' => AdminActivityLog::query()
                ->with('admin')
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }
}
