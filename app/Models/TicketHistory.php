<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketHistory extends Model
{
    use HasFactory;

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_ORIGINAL_SNAPSHOT_BACKFILLED = 'original_snapshot_backfilled';
    public const FIELD_SNAPSHOT = 'snapshot';

    public const UPDATED_AT = null;

    protected $table = 'ticket_history';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'event',
        'field',
        'old_value',
        'new_value',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
