<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reference',
        'doctor_id',
        'issue_date',
        'from_date',
        'to_date',
        'total_amount',
        'doctor_share',
        'mashora_share',
        'paid_at',
        'payment_status',
        'link_pdf',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the doctor that owns the invoice.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the items for the invoice.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
