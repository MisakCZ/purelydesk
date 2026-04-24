<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class HelpdeskAuth
{
    public function user(): ?User
    {
        $authenticatedUser = auth()->user();

        if ($authenticatedUser instanceof User) {
            return $authenticatedUser;
        }

        if (! config('helpdesk.auth.allow_temporary_user_fallback', false)) {
            return null;
        }

        // Temporary development fallback. Must stay explicitly disabled in normal auth mode.
        return User::query()->orderBy('id')->first();
    }

    public function requireUser(
        string $message,
        string $field = 'user',
        ?string $errorBag = null,
    ): User {
        $user = $this->user();

        if ($user instanceof User) {
            return $user;
        }

        $exception = ValidationException::withMessages([
            $field => $message,
        ]);

        if ($errorBag !== null) {
            $exception->errorBag($errorBag);
        }

        throw $exception;
    }
}
