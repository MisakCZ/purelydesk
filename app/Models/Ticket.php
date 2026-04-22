<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Ticket extends Model
{
    use HasFactory;

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_RESTRICTED = 'restricted';

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'visibility',
        'department_id',
        'requester_id',
        'assignee_id',
        'ticket_status_id',
        'ticket_priority_id',
        'ticket_category_id',
        'due_at',
        'last_activity_at',
        'closed_at',
        'is_pinned',
        'pinned_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'closed_at' => 'datetime',
            'is_pinned' => 'boolean',
            'pinned_at' => 'datetime',
        ];
    }

    public static function supportsPinning(): bool
    {
        static $supportsPinning;

        if ($supportsPinning === null) {
            $supportsPinning = Schema::hasColumn('tickets', 'is_pinned')
                && Schema::hasColumn('tickets', 'pinned_at');
        }

        return $supportsPinning;
    }

    public static function visibilityOptions(): array
    {
        return [
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_RESTRICTED => 'Restricted',
        ];
    }

    public function scopeVisibleTo(Builder $query, ?User $user, bool $administrativeMode = false): Builder
    {
        return $query->where(function (Builder $query) use ($user, $administrativeMode): void {
            $query->where('visibility', self::VISIBILITY_PUBLIC);

            if ($administrativeMode) {
                $query->orWhere('visibility', self::VISIBILITY_RESTRICTED);

                return;
            }

            if (! $user instanceof User) {
                return;
            }

            $query->orWhere(function (Builder $query) use ($user): void {
                $query
                    ->where('visibility', self::VISIBILITY_RESTRICTED)
                    ->where(function (Builder $query) use ($user): void {
                        $query
                            ->where('requester_id', $user->id)
                            ->orWhere('assignee_id', $user->id);
                    });
            });
        });
    }

    public function isVisibleTo(?User $user, bool $administrativeMode = false): bool
    {
        if ($this->visibility !== self::VISIBILITY_RESTRICTED) {
            return true;
        }

        if ($administrativeMode) {
            return true;
        }

        if (! $user instanceof User) {
            return false;
        }

        if ((int) $this->requester_id === (int) $user->id || (int) $this->assignee_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function publicComments(): HasMany
    {
        return $this->comments()->where('visibility', 'public');
    }

    public function publicRootComments(): HasMany
    {
        return $this->comments()
            ->where('visibility', 'public')
            ->whereNull('parent_id');
    }

    public function internalComments(): HasMany
    {
        return $this->comments()->where('visibility', 'internal');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_watchers')->withTimestamps();
    }

    public function watcherEntries(): HasMany
    {
        return $this->hasMany(TicketWatcher::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }
}
