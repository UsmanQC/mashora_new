<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['duration', 'title', 'title_ar'])]
class Duration extends Model
{
    use SoftDeletes;

    /**
     * The primary key is the session length in minutes (legacy schema).
     */
    protected $primaryKey = 'duration';

    public $incrementing = false;

    protected $keyType = 'int';

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_duration', 'duration', 'doctor_id')
            ->withPivot('price');
    }
}
