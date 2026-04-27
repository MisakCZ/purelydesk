<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketWatcher extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_manual',
        'is_auto_participant',
    ];

    protected function casts(): array
    {
        return [
            'is_manual' => 'boolean',
            'is_auto_participant' => 'boolean',
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
