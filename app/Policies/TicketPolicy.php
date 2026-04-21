<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(?User $user, Ticket $ticket): bool
    {
        return $ticket->isVisibleTo($user, $this->administrativeModeEnabled());
    }

    public function update(?User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    private function administrativeModeEnabled(): bool
    {
        return (bool) config('helpdesk.admin_mode', false);
    }
}
