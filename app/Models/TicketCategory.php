<?php

namespace App\Models;

use Database\Factories\TicketCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    /** @use HasFactory<TicketCategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'audience',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<TicketCategory>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<TicketCategory>  $query
     */
    public function scopeForAudience(Builder $query, string $audience): Builder
    {
        return $query->where('audience', $audience);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function displayName(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->name_ar)) {
            return (string) $this->name_ar;
        }

        return (string) $this->name;
    }
}
