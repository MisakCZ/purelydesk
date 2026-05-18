<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReadState extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'last_read_activity_id',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
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

    public function lastReadActivity(): BelongsTo
    {
        return $this->belongsTo(TicketActivity::class, 'last_read_activity_id');
    }
}
