<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'name_ar',
    'email',
    'phone',
    'gender',
    'status',
    'spoken_languages',
])]
class Doctor extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsToMany<Duration, $this>
     */
    public function durations(): BelongsToMany
    {
        return $this->belongsToMany(Duration::class, 'doctor_duration', 'doctor_id', 'duration')
            ->withPivot('price');
    }

    public function displayName(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->name_ar)) {
            return (string) $this->name_ar;
        }

        return (string) ($this->name ?? $this->name_ar ?? '');
    }
}
