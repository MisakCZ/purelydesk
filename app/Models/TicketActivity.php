<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    public const TYPE_TICKET_CREATED = 'ticket_created';
    public const TYPE_PUBLIC_COMMENT = 'public_comment';
    public const TYPE_INTERNAL_NOTE = 'internal_note';
    public const TYPE_TICKET_UPDATED = 'ticket_updated';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_ASSIGNEE_CHANGED = 'assignee_changed';
    public const TYPE_PRIORITY_CHANGED = 'priority_changed';
    public const TYPE_CATEGORY_CHANGED = 'category_changed';
    public const TYPE_VISIBILITY_CHANGED = 'visibility_changed';
    public const TYPE_EXPECTED_RESOLUTION_CHANGED = 'expected_resolution_changed';
    public const TYPE_RESOLVED = 'resolved';
    public const TYPE_CLOSED = 'closed';
    public const TYPE_PROBLEM_PERSISTS = 'problem_persists';
    public const TYPE_REQUESTER_CHANGED = 'requester_changed';
    public const TYPE_PINNED = 'pinned';
    public const TYPE_UNPINNED = 'unpinned';
    public const TYPE_ARCHIVED = 'archived';
    public const TYPE_RESTORED = 'restored';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_INTERNAL = 'internal';

    public const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'type',
        'visibility',
        'subject_type',
        'subject_id',
        'summary_key',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function isInternal(): bool
    {
        return $this->visibility === self::VISIBILITY_INTERNAL;
    }
}
