<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(?User $user, Ticket $ticket): bool
    {
        return $ticket->isVisibleTo($user);
    }

    public function create(?User $user): bool
    {
        return $user instanceof User;
    }

    public function update(?User $user, Ticket $ticket): bool
    {
        return $this->updateBasic($user, $ticket);
    }

    public function updateBasic(?User $user, Ticket $ticket): bool
    {
        if (! $user instanceof User || ! $this->view($user, $ticket)) {
            return false;
        }

        if ($user->isAdmin() || $user->isSolver()) {
            return true;
        }

        return $this->isRequester($user, $ticket) && ! $ticket->isRequesterEditLocked();
    }

    public function updatePriority(?User $user, Ticket $ticket): bool
    {
        return $this->updateBasic($user, $ticket);
    }

    public function updateCategory(?User $user, Ticket $ticket): bool
    {
        return $this->updateBasic($user, $ticket);
    }

    public function updateStatus(?User $user, Ticket $ticket): bool
    {
        return $this->canManageWorkflow($user, $ticket);
    }

    public function updateRequester(?User $user, Ticket $ticket): bool
    {
        return $this->canManageWorkflow($user, $ticket);
    }

    public function updateAssignee(?User $user, Ticket $ticket): bool
    {
        return $this->canManageWorkflow($user, $ticket);
    }

    public function updateVisibility(?User $user, Ticket $ticket): bool
    {
        return $user instanceof User
            && $user->isAdmin()
            && $this->view($user, $ticket);
    }

    public function updateExpectedResolution(?User $user, Ticket $ticket): bool
    {
        return $this->canManageWorkflow($user, $ticket);
    }

    public function updatePin(?User $user, Ticket $ticket): bool
    {
        return $this->canManagePinnedState($user, $ticket);
    }

    public function managePinnedState(?User $user): bool
    {
        return $user instanceof User
            && ($user->isAdmin() || $user->isSolver());
    }

    public function commentPublic(?User $user, Ticket $ticket): bool
    {
        if (! $user instanceof User || ! $this->view($user, $ticket)) {
            return false;
        }

        if ($user->isAdmin() || $user->isSolver()) {
            return true;
        }

        if ($ticket->visibility === Ticket::VISIBILITY_PUBLIC) {
            return true;
        }

        return $this->isRequester($user, $ticket);
    }

    public function viewInternalNotes(?User $user, Ticket $ticket): bool
    {
        return $this->canManageInternalNotes($user, $ticket);
    }

    public function commentInternal(?User $user, Ticket $ticket): bool
    {
        return $this->canManageInternalNotes($user, $ticket);
    }

    public function watch(?User $user, Ticket $ticket): bool
    {
        return $user instanceof User
            && $this->view($user, $ticket);
    }

    public function confirmResolution(?User $user, Ticket $ticket): bool
    {
        return $user instanceof User
            && $this->isRequester($user, $ticket)
            && $ticket->hasStatusSlug('resolved');
    }

    public function reportProblemPersists(?User $user, Ticket $ticket): bool
    {
        return $this->confirmResolution($user, $ticket);
    }

    private function canManageWorkflow(?User $user, Ticket $ticket): bool
    {
        return $user instanceof User
            && $this->view($user, $ticket)
            && ($user->isAdmin() || $user->isSolver());
    }

    private function canManagePinnedState(?User $user, Ticket $ticket): bool
    {
        return $this->managePinnedState($user)
            && $this->view($user, $ticket);
    }

    private function canManageInternalNotes(?User $user, Ticket $ticket): bool
    {
        return $user instanceof User
            && $this->view($user, $ticket)
            && ($user->isAdmin() || $user->isSolver());
    }

    private function isRequester(User $user, Ticket $ticket): bool
    {
        return (int) $ticket->requester_id === (int) $user->id;
    }
}
