<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(?User $user): bool
    {
        return $this->manage($user);
    }

    public function manage(?User $user): bool
    {
        return $user instanceof User
            && ($user->isAdmin() || $user->isSolver());
    }

    public function create(?User $user): bool
    {
        return $this->manage($user);
    }

    public function update(?User $user, Announcement $announcement): bool
    {
        return $this->manage($user);
    }

    public function delete(?User $user, Announcement $announcement): bool
    {
        return $this->manage($user);
    }
}
