<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugStrength extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['dosage'];

    /**
     * Get the drug that owns the strength.
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }
}
