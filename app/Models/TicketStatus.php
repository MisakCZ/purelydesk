<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    use HasFactory;

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
}
