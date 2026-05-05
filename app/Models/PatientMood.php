<?php

namespace App\Models;

use Database\Factories\PatientMoodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientMood extends Model
{
    /** @use HasFactory<PatientMoodFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'patient_moods';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'mood',
        'comments',
        'date',
        'is_shared',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_shared' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
