<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    public const LEGACY_VISIBILITY_RESTRICTED = 'restricted';
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_INTERNAL = 'internal';
    public const VISIBILITY_PRIVATE = 'private';

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
        'resolved_at',
        'auto_close_at',
        'closed_at',
        'is_pinned',
        'pinned_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'resolved_at' => 'datetime',
            'auto_close_at' => 'datetime',
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
            self::VISIBILITY_PUBLIC => self::visibilityLabel(self::VISIBILITY_PUBLIC),
            self::VISIBILITY_INTERNAL => self::visibilityLabel(self::VISIBILITY_INTERNAL),
            self::VISIBILITY_PRIVATE => self::visibilityLabel(self::VISIBILITY_PRIVATE),
        ];
    }

    public static function visibilityLabel(?string $visibility): string
    {
        $normalizedVisibility = $visibility === self::LEGACY_VISIBILITY_RESTRICTED
            ? self::VISIBILITY_PRIVATE
            : (string) $visibility;

        if ($normalizedVisibility === '') {
            return '—';
        }

        $translationKey = 'tickets.values.visibility.'.$normalizedVisibility;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return Str::headline(str_replace('_', ' ', $normalizedVisibility));
    }

    public function translatedVisibilityLabel(): string
    {
        return self::visibilityLabel($this->normalizedVisibility());
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('visibility', self::VISIBILITY_PUBLIC);

            if ($user->isAdmin()) {
                $query->orWhereIn('visibility', [
                    self::VISIBILITY_INTERNAL,
                    self::VISIBILITY_PRIVATE,
                    self::LEGACY_VISIBILITY_RESTRICTED,
                ]);

                return;
            }

            if ($user->isSolver()) {
                $query->orWhere('visibility', self::VISIBILITY_INTERNAL)
                    ->orWhere(function (Builder $query) use ($user): void {
                        $query
                            ->whereIn('visibility', $this->privateVisibilityValues())
                            ->where(function (Builder $query) use ($user): void {
                                $query
                                    ->where('requester_id', $user->id)
                                    ->orWhere('assignee_id', $user->id);
                            });
                    });

                return;
            }

            $query->orWhere(function (Builder $query) use ($user): void {
                $query
                    ->where('visibility', self::VISIBILITY_INTERNAL)
                    ->where('requester_id', $user->id);
            })->orWhere(function (Builder $query) use ($user): void {
                $query
                    ->whereIn('visibility', $this->privateVisibilityValues())
                    ->where(function (Builder $query) use ($user): void {
                        $query
                            ->where('requester_id', $user->id)
                            ->orWhere('assignee_id', $user->id);
                    });
            });
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $visibility = $this->normalizedVisibility();

        if ($user->isAdmin()) {
            return true;
        }

        if ($visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        if ($visibility === self::VISIBILITY_INTERNAL) {
            if ($user->isSolver()) {
                return true;
            }

            return (int) $this->requester_id === (int) $user->id;
        }

        if ($visibility !== self::VISIBILITY_PRIVATE) {
            return false;
        }

        if ((int) $this->requester_id === (int) $user->id || (int) $this->assignee_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    public function normalizedVisibility(): string
    {
        if ($this->visibility === self::LEGACY_VISIBILITY_RESTRICTED) {
            return self::VISIBILITY_PRIVATE;
        }

        return (string) $this->visibility;
    }

    public function statusSlug(): ?string
    {
        if ($this->relationLoaded('status') && $this->status !== null) {
            $statusAttributes = $this->status->getAttributes();

            if (array_key_exists('slug', $statusAttributes)) {
                return $statusAttributes['slug'];
            }
        }

        return $this->status()
            ->value('slug');
    }

    public function statusIsClosed(): bool
    {
        if ($this->relationLoaded('status') && $this->status !== null) {
            $statusAttributes = $this->status->getAttributes();

            if (array_key_exists('is_closed', $statusAttributes)) {
                return (bool) $statusAttributes['is_closed'];
            }
        }

        return (bool) $this->status()
            ->value('is_closed');
    }

    public function hasStatusSlug(string|array $slugs): bool
    {
        $slug = $this->statusSlug();

        if ($slug === null) {
            return false;
        }

        return in_array($slug, (array) $slugs, true);
    }

    public function isRequesterEditLocked(): bool
    {
        return $this->hasStatusSlug([
            'resolved',
            'closed',
            'cancelled',
        ]);
    }

    private function privateVisibilityValues(): array
    {
        return [
            self::VISIBILITY_PRIVATE,
            self::LEGACY_VISIBILITY_RESTRICTED,
        ];
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
