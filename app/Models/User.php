<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'username',
    'name',
    'display_name',
    'department',
    'email',
    'email_verified_at',
    'password',
    'preferred_locale',
    'ticket_index_preferences',
    'ldap_dn',
    'external_id',
    'auth_source',
    'is_active',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function displayName(): string
    {
        return $this->display_name
            ?: $this->name
            ?: $this->username
            ?: (string) $this->email;
    }

    public function loginName(): string
    {
        return $this->username
            ?: $this->name
            ?: $this->displayName();
    }

    public function hasRole(string $roleSlug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $roleSlug);
        }

        return $this->roles()
            ->where('slug', $roleSlug)
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::SLUG_ADMIN);
    }

    public function isSolver(): bool
    {
        return $this->hasRole(Role::SLUG_SOLVER);
    }

    public function scopeAssignableSolvers(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->whereHas('roles', fn (Builder $query) => $query->where('slug', Role::SLUG_SOLVER));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withPivot('is_manager')
            ->withTimestamps();
    }

    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    public function ticketComments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function ticketAttachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function watchedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_watchers')
            ->withPivot(['is_manual', 'is_auto_participant'])
            ->withTimestamps();
    }

    public function ticketHistory(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'author_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'ticket_index_preferences' => 'array',
            'password' => 'hashed',
        ];
    }
}
