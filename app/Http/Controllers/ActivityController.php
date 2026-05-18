<?php

namespace App\Http\Controllers;

use App\Services\TicketActivityService;
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
}
