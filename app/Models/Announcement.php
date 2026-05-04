<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Announcement extends Model
{
    use HasFactory;

    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_OUTAGE = 'outage';
    public const TYPE_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'department_id',
        'author_id',
        'title',
        'body',
        'type',
        'visibility',
        'is_active',
        'is_pinned',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_pinned' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_INFO => self::translatedTypeLabel(self::TYPE_INFO),
            self::TYPE_WARNING => self::translatedTypeLabel(self::TYPE_WARNING),
            self::TYPE_OUTAGE => self::translatedTypeLabel(self::TYPE_OUTAGE),
            self::TYPE_MAINTENANCE => self::translatedTypeLabel(self::TYPE_MAINTENANCE),
        ];
    }

    public static function translatedTypeLabel(?string $type): string
    {
        $type = $type ?: self::TYPE_INFO;
        $translationKey = 'announcements.types.'.$type;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return Str::headline(str_replace('_', ' ', $type));
    }

    public static function translatedVisibilityLabel(?string $visibility): string
    {
        $visibility = $visibility ?: 'public';
        $translationKey = 'announcements.values.visibility.'.$visibility;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return Str::headline(str_replace('_', ' ', $visibility));
    }

    public static function hasTypeColumn(): bool
    {
        static $hasTypeColumn;

        if ($hasTypeColumn === null) {
            $hasTypeColumn = Schema::hasColumn('announcements', 'type');
        }

        return $hasTypeColumn;
    }

    public static function supportsPinning(): bool
    {
        static $supportsPinning;

        if ($supportsPinning === null) {
            $supportsPinning = Schema::hasColumn('announcements', 'is_pinned');
        }

        return $supportsPinning;
    }

    public function getTypeAttribute(?string $value): string
    {
        return $value ?: self::TYPE_INFO;
    }

    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query
            ->where('is_active', true)
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopePublicVisible(Builder $query): void
    {
        $query->where('visibility', 'public');
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
