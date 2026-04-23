<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    use HasFactory;

    public const BADGE_TONE_SLATE = 'slate';
    public const BADGE_TONE_BLUE = 'blue';
    public const BADGE_TONE_AMBER = 'amber';
    public const BADGE_TONE_VIOLET = 'violet';
    public const BADGE_TONE_CYAN = 'cyan';
    public const BADGE_TONE_GREEN = 'green';
    public const BADGE_TONE_NEUTRAL = 'neutral';
    public const BADGE_TONE_RED = 'red';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'sort_order',
        'is_default',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
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

    public static function badgeToneForSlug(?string $slug): string
    {
        return match ($slug) {
            'assigned' => self::BADGE_TONE_BLUE,
            'in_progress' => self::BADGE_TONE_AMBER,
            'waiting_user' => self::BADGE_TONE_VIOLET,
            'waiting_third_party' => self::BADGE_TONE_CYAN,
            'resolved' => self::BADGE_TONE_GREEN,
            'closed' => self::BADGE_TONE_NEUTRAL,
            'cancelled' => self::BADGE_TONE_RED,
            default => self::BADGE_TONE_SLATE,
        };
    }
}
