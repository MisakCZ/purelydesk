<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

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
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'closed_at' => 'datetime',
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
