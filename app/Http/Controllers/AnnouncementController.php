<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class AnnouncementController extends Controller
{
    use ResolvesHelpdeskUser;

    public function index(): View
    {
        $this->authorizeAnnouncementManagement();

        return view('announcements.index', [
            'announcements' => Announcement::query()
                ->with('author:id,name')
                ->orderByDesc('created_at')
                ->get(),
            'announcementTypes' => Announcement::typeOptions(),
        ]);
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorizeAnnouncementManagement();

        return view('announcements.edit', [
            'announcement' => $announcement,
            'announcementTypes' => Announcement::typeOptions(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        Announcement::query()->create($this->buildAnnouncementAttributes(
            $request,
            $this->resolveAuthor()->id,
        ));

        return redirect()
            ->route('announcements.index')
            ->with('status', 'Oznámení bylo úspěšně vytvořeno.');
    }

    public function update(StoreAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->buildAnnouncementAttributes(
            $request,
            $announcement->author_id,
        ));

        return redirect()
            ->route('announcements.index')
            ->with('status', 'Oznámení bylo úspěšně upraveno.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorizeAnnouncementManagement();

        $announcement->delete();

        return redirect()
            ->route('announcements.index')
            ->with('status', 'Oznámení bylo úspěšně smazáno.');
    }

    private function resolveAuthor(): User
    {
        return $this->requireHelpdeskUser(
            'Oznámení zatím nelze uložit, protože v databázi neexistuje žádný uživatel.',
            'author',
        );
    }

    private function authorizeAnnouncementManagement(): void
    {
        abort_unless(
            app(AnnouncementPolicy::class)->manage($this->currentHelpdeskUser()),
            403,
        );
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function buildAnnouncementAttributes(StoreAnnouncementRequest $request, ?int $authorId): array
    {
        $validated = $request->validated();

        $attributes = [
            'department_id' => null,
            'author_id' => $authorId,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'visibility' => 'public',
            'is_active' => $request->has('is_active'),
            'starts_at' => $this->parseDateTime($validated['starts_at'] ?? null),
            'ends_at' => $this->parseDateTime($validated['ends_at'] ?? null),
        ];

        if (Announcement::hasTypeColumn()) {
            $attributes['type'] = $validated['type'];
        }

        return $attributes;
    }
}
