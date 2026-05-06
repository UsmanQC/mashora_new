<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'working_day_id',
        'start_time',
        'end_time',
    ];

    /**
     * @return BelongsTo<WorkingDay, $this>
     */
    public function workingDay(): BelongsTo
    {
        return $this->belongsTo(WorkingDay::class);
    }
}
