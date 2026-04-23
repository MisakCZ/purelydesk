<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function translatedName(): string
    {
        if ($this->slug === null || $this->slug === '') {
            return $this->name;
        }

        $translationKey = 'tickets.values.categories.'.$this->slug;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return $this->name ?: Str::headline(str_replace('_', ' ', $this->slug));
    }
}
