<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::query()
                ->orderByDesc('publish_date')
                ->orderByDesc('created_at')
                ->get(),
            'editing' => $request->filled('edit')
                ? Announcement::query()->find((int) $request->query('edit'))
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAnnouncement($request);
        $data['created_by'] = $request->user()->id;

        $announcement = Announcement::create($data);

        $this->audit->log($request->user(), 'created announcement', 'announcements', ['id' => $announcement->id]);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement created.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validateAnnouncement($request));

        $this->audit->log($request->user(), 'updated announcement', 'announcements', ['id' => $announcement->id]);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement updated.');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $id = $announcement->id;
        $announcement->delete();

        $this->audit->log($request->user(), 'deleted announcement', 'announcements', ['id' => $id]);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement deleted.');
    }

    /** @return array<string, mixed> */
    private function validateAnnouncement(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string'],
            'publish_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in([
                Announcement::STATUS_DRAFT,
                Announcement::STATUS_PUBLISHED,
                Announcement::STATUS_ARCHIVED,
            ])],
        ]);

        $data['publish_date'] = $data['publish_date'] ?? null;

        return $data;
    }
}
