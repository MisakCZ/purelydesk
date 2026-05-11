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

    private const ALLOWED_BODY_HTML_TAGS = '<a><br><p><strong><b><em><i><u><ul><ol><li>';

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

    public static function sanitizeBodyHtml(?string $body): string
    {
        $html = preg_replace('/<(script|style|iframe|object|embed)\b[^>]*>.*?<\/\1>/is', '', (string) $body) ?? '';
        $html = strip_tags($html, self::ALLOWED_BODY_HTML_TAGS);

        return preg_replace_callback('/<([a-z][a-z0-9]*)(\s[^>]*)?>/i', function (array $matches): string {
            $tag = strtolower($matches[1]);

            if ($tag !== 'a') {
                return '<'.$tag.'>';
            }

            $attributes = self::sanitizeLinkAttributes($matches[2] ?? '');

            return '<a'.$attributes.'>';
        }, $html) ?? '';
    }

    public function bodyHtml(): string
    {
        return self::sanitizeBodyHtml($this->body);
    }

    private static function sanitizeLinkAttributes(string $attributeText): string
    {
        preg_match_all(
            '/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^"\'>\s]+))/',
            $attributeText,
            $matches,
            PREG_SET_ORDER,
        );

        $attributes = [];

        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = html_entity_decode($match[3] ?? $match[4] ?? $match[5] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($name === 'href' && self::isSafeLinkHref($value)) {
                $attributes['href'] = e($value);
            }

            if ($name === 'title') {
                $attributes['title'] = e($value);
            }

            if ($name === 'target' && in_array($value, ['_blank', '_self'], true)) {
                $attributes['target'] = e($value);
            }
        }

        if (isset($attributes['target']) && $attributes['target'] === '_blank') {
            $attributes['rel'] = 'noopener noreferrer';
        }

        return collect($attributes)
            ->map(fn (string $value, string $name): string => ' '.$name.'="'.$value.'"')
            ->implode('');
    }

    private static function isSafeLinkHref(string $href): bool
    {
        $href = trim($href);

        if ($href === '') {
            return false;
        }

        if (str_starts_with($href, '/') || str_starts_with($href, '#')) {
            return true;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);

        return $scheme !== null && in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true);
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
