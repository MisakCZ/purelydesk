<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketPriority extends Model
{
    use HasFactory;

    public const BADGE_TONE_SLATE = 'slate';
    public const BADGE_TONE_BLUE = 'blue';
    public const BADGE_TONE_AMBER = 'amber';
    public const BADGE_TONE_RED = 'red';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'sort_order',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function badgeTone(): string
    {
        return self::badgeToneForSlug($this->slug);
    }

    public function badgeToneClass(): string
    {
        return 'badge-tone-'.self::badgeToneForSlug($this->slug);
    }

    public function translatedName(?string $locale = null): string
    {
        return self::translatedNameForSlug($this->slug, $this->name, $locale);
    }

    public static function translatedNameForSlug(?string $slug, ?string $fallback = null, ?string $locale = null): string
    {
        if ($slug === null || $slug === '') {
            return $fallback ?: '—';
        }

        $translationKey = 'tickets.values.priorities.'.$slug;
        $translated = __($translationKey, [], $locale);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return $fallback ?: Str::headline(str_replace('_', ' ', $slug));
    }

    public static function badgeToneForSlug(?string $slug): string
    {
        return match ($slug) {
            'normal' => self::BADGE_TONE_BLUE,
            'high' => self::BADGE_TONE_AMBER,
            'critical' => self::BADGE_TONE_RED,
            default => self::BADGE_TONE_SLATE,
        };
    }
}
