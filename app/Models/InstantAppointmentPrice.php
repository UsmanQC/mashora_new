<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstantAppointmentPrice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['duration', 'price'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    /**
     * FK `duration` references {@see Duration::$duration} (non-incrementing PK).
     */
    public function durationOption(): BelongsTo
    {
        return $this->belongsTo(Duration::class, 'duration', 'duration');
    }
}
