<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Services\TicketActivityService;
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use ResolvesHelpdeskUser;

    public function index(TicketActivityService $activities): View
    {
        $user = $this->requireHelpdeskUser(__('auth.login.required'), 'user');

        return view('activities.index', [
            'activities' => $activities->unreadActivitiesForUser($user),
        ]);
    }

    public function markAllRead(TicketActivityService $activities): RedirectResponse
    {
        $user = $this->requireHelpdeskUser(__('auth.login.required'), 'user');
        $markedCount = $activities->markAllVisibleRead($user);

        return redirect()
            ->route('activities.index')
            ->with('status', trans_choice('activities.flash.marked_all_read', $markedCount, ['count' => $markedCount]));
    }

    public function poll(Request $request, TicketActivityService $activities): JsonResponse
    {
        $user = $this->requireHelpdeskUser(__('auth.login.required'), 'user');
        $ticket = null;
        $ticketId = $request->integer('ticket_id');
        $hasTicketScope = $ticketId > 0;

        if ($hasTicketScope) {
            $ticket = Ticket::query()->find($ticketId);

            if ($ticket instanceof Ticket && ! app(TicketPolicy::class)->view($user, $ticket)) {
                $ticket = null;
            }
        }

        return response()->json([
            'unread_count' => $activities->unreadActivityCountForUser($user),
            'latest_activity_id' => $hasTicketScope && ! ($ticket instanceof Ticket)
                ? null
                : $activities->latestVisibleActivityIdForUser($user, $ticket),
            'ticket_id' => $ticket?->id,
        ]);
    }
}
