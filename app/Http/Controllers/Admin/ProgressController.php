<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlayerProgressService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgressController extends Controller
{
    public function __construct(private readonly PlayerProgressService $progress) {}

    private function listing(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'not_started', 'in_progress', 'completed'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? 'all';
        $rows = $this->progress->players($search, $status)->paginate(10)
            ->withPath(route('admin.progress.index'))->withQueryString()
            ->through(fn ($row) => $this->progress->describe($row));
        $avatars = app(\App\Services\AvatarService::class)->forIds($rows->getCollection()->pluck('id')->all());
        $rows->through(fn ($row) => [...$row, 'profile_picture_url' => $avatars[$row['id']] ?? app(\App\Services\AvatarService::class)->defaultUrl()]);

        return compact('rows', 'search', 'status');
    }

    public function index(Request $request)
    {
        return view('admin.progress.index', $this->listing($request));
    }

    public function data(Request $request)
    {
        $html = view('admin.progress.results', $this->listing($request))->render();
        $response = response()->json(['html' => $html]);
        $response->setEtag(hash('sha256', $html));
        $response->headers->set('Cache-Control', 'private, no-cache');
        $response->isNotModified($request);

        return $response;
    }
}
