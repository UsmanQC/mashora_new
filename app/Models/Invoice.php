<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'wallet_settled_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'from_date' => 'date',
            'to_date' => 'date',
            'paid_at' => 'datetime',
            'wallet_settled_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'doctor_share' => 'decimal:2',
            'mashora_share' => 'decimal:2',
        ];
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

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
