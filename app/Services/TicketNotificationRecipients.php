<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TicketNotificationRecipients
{
    public function __construct(
        private readonly TicketPolicy $ticketPolicy,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function forTicket(Ticket $ticket, string $event, ?User $actor = null, bool $excludeActor = true): Collection
    {
        $ticket->loadMissing([
            'requester:id,name,display_name,username,email,preferred_locale',
            'assignee:id,name,display_name,username,email,preferred_locale',
            'watchers:id,name,display_name,username,email,preferred_locale',
        ]);

        return collect([
            $ticket->requester,
            $ticket->assignee,
            ...$ticket->watchers,
            ...$this->operationalRecipientsForCreatedTicket($event),
        ])
            ->filter(fn ($user) => $user instanceof User)
            ->when(
                $excludeActor && $actor instanceof User,
                fn (Collection $users) => $users->reject(fn (User $user) => (int) $user->id === (int) $actor->id),
            )
            ->filter(fn (User $user) => filled($user->email))
            ->filter(fn (User $user) => $this->ticketPolicy->view($user, $ticket))
            ->unique(fn (User $user) => $this->deduplicationKey($user))
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function operationalRecipientsForCreatedTicket(string $event): Collection
    {
        if ($event !== 'created') {
            return collect();
        }

        $roleSlugs = collect();

        if (config('helpdesk.notifications.mail.notify_solvers_on_new_tickets')) {
            $roleSlugs->push('solver');
        }

        if (config('helpdesk.notifications.mail.notify_admins_on_new_tickets')) {
            $roleSlugs->push('admin');
        }

        if ($roleSlugs->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('slug', $roleSlugs->all()))
            ->get(['id', 'name', 'display_name', 'username', 'email', 'preferred_locale']);
    }

    private function deduplicationKey(User $user): string
    {
        return $user->email !== null && $user->email !== ''
            ? 'email:'.mb_strtolower($user->email)
            : 'id:'.$user->id;
    }
}
