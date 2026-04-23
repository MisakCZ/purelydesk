<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class TicketComment extends Model
{
    use HasFactory;

    protected $touches = [
        'ticket',
    ];

    protected $fillable = [
        'ticket_id',
        'user_id',
        'parent_id',
        'visibility',
        'body',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    public static function supportsThreading(): bool
    {
        static $supportsThreading;

        if ($supportsThreading === null) {
            $supportsThreading = Schema::hasColumn('ticket_comments', 'parent_id');
        }

        return $supportsThreading;
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function publicReplies(): HasMany
    {
        return $this->replies()->where('visibility', 'public');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function scopePublicVisible(Builder $query): void
    {
        $query->where('visibility', 'public');
    }

    public function scopeInternalVisible(Builder $query): void
    {
        $query->where('visibility', 'internal');
    }

    public function scopeRootComments(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
