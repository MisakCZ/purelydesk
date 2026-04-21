<?php

namespace App\Support;

use App\Models\User;

trait ResolvesHelpdeskUser
{
    protected function currentHelpdeskUser(): ?User
    {
        $authenticatedUser = auth()->user();

        if ($authenticatedUser instanceof User) {
            return $authenticatedUser;
        }

        // Temporary fallback until authentication is integrated.
        return User::query()->orderBy('id')->first();
    }
}
