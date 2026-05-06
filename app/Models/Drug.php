<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'scientific_name',
        'trade_name',
        'strength',
        'strength_unit',
        'pharmaceutical_form',
        'administration_route',
        'flag',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flag' => 'boolean',
        ];
    }

    /**
     * Dosage rows (prod {@see \App\Http\Controllers\Admin\DrugsController}).
     */
    public function strengths(): HasMany
    {
        return $this->hasMany(DrugStrength::class);
    }
}
