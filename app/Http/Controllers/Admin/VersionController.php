<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameVersion;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VersionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        return view('admin.versions.index', [
            'versions' => GameVersion::query()
                ->orderByDesc('release_date')
                ->orderByDesc('created_at')
                ->get(),
            'editing' => $request->filled('edit')
                ? GameVersion::query()->find((int) $request->query('edit'))
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $version = GameVersion::create($this->validateVersion($request));

        $this->audit->log($request->user(), 'created game version', 'game_versions', [
            'id' => $version->id, 'version_number' => $version->version_number,
        ]);

        return redirect()->route('admin.versions.index')->with('status', 'Game version created.');
    }

    public function update(Request $request, GameVersion $version)
    {
        $version->update($this->validateVersion($request));

        $this->audit->log($request->user(), 'updated game version', 'game_versions', ['id' => $version->id]);

        return redirect()->route('admin.versions.index')->with('status', 'Game version updated.');
    }

    public function destroy(Request $request, GameVersion $version)
    {
        $id = $version->id;
        $version->delete();

        $this->audit->log($request->user(), 'deleted game version', 'game_versions', ['id' => $id]);

        return redirect()->route('admin.versions.index')->with('status', 'Game version deleted.');
    }

    /** @return array<string, mixed> */
    private function validateVersion(Request $request): array
    {
        $data = $request->validate([
            'version_number' => ['required', 'string', 'max:50'],
            'platform' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'release_notes' => ['nullable', 'string'],
            'release_date' => ['nullable', 'date'],
            'download_url' => ['nullable', 'url', 'max:255'],
        ]);

        $data['release_notes'] = $data['release_notes'] ?? null;
        $data['release_date'] = $data['release_date'] ?? null;
        $data['download_url'] = $data['download_url'] ?? null;

        return $data;
    }
}
