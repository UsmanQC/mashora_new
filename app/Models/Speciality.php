<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'title_ar', 'status'])]
class Speciality extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * @return BelongsToMany<Doctor, $this>
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_speciality', 'speciality_id', 'doctor_id');
    }
}
