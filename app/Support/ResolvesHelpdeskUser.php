<?php

namespace App\Support;

use App\Models\User;

trait ResolvesHelpdeskUser
{
    protected function currentHelpdeskUser(): ?User
    {
        return app(HelpdeskAuth::class)->user();
    }

    protected function requireHelpdeskUser(
        string $message,
        string $field = 'user',
        ?string $errorBag = null,
    ): User {
        return app(HelpdeskAuth::class)->requireUser($message, $field, $errorBag);
    }
}
