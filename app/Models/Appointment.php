<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'scheduled_at' => 'datetime',
            'prescription_not_needed' => 'boolean',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'extend_at' => 'datetime',
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'wallet_amount' => 'decimal:2',
            'doctor_share' => 'decimal:2',
            'mashora_share' => 'decimal:2',
        ];
    }

    protected $fillable = [
        'appointment_number',
        'doctor_id',
        'user_id',
        'scheduled_at',
        'appointment_date',
        'start_time',
        'end_time',
        'duration',
        'appointment_for',
        'patient_name',
        'patient_email',
        'patient_phone',
        'patient_notes',
        'amount',
        'discount',
        'tax',
        'total',
        'wallet_amount',
        'doctor_share',
        'mashora_share',
        'appointment_type',
        'instant_counseling',
        'status',
        'cancel_status',
        'payment_session_id',
        'payment_invoice_id',
        'payment_invoice_url',
        'refund_payment_invoice_id',
        'refund_payment_response',
        'prescription_not_needed',
        'actual_start_at',
        'actual_end_at',
        'extend_at',
        'extend_duration',
    ];

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeAvailableFor($query, int $doctorId, string $startTime, string $endTime, ?string $date = null)
    {
        $date = $date ?: now()->format('Y-m-d');

        return $query->where(function ($subquery) use ($startTime, $endTime): void {
            $subquery->where(function ($subsubquery) use ($startTime, $endTime): void {
                $subsubquery->where('start_time', '>=', $startTime)
                    ->where('start_time', '<', $endTime);
            })
                ->orWhere(function ($subsubquery) use ($startTime, $endTime): void {
                    $subsubquery->where('end_time', '>', $startTime)
                        ->where('end_time', '<=', $endTime);
                })
                ->orWhere(function ($subsubquery) use ($startTime, $endTime): void {
                    $subsubquery->where('start_time', '<', $startTime)
                        ->where('end_time', '>', $endTime);
                });
        })
            ->where('doctor_id', $doctorId)
            ->where('appointment_date', $date);
    }

    public function formattedSessionStart(): string
    {
        if ($this->appointment_date === null || $this->start_time === null) {
            return '';
        }

        try {
            $datePart = $this->appointment_date instanceof Carbon
                ? $this->appointment_date->format('Y-m-d')
                : Carbon::parse($this->appointment_date)->format('Y-m-d');

            return Carbon::parse($datePart.' '.(string) $this->start_time)->timezone(config('app.timezone'))->format('h:i A');
        } catch (\Throwable) {
            return (string) $this->start_time;
        }
    }

    /**
     * @return HasOne<Diagnosis, $this>
     */
    public function diagnosis(): HasOne
    {
        return $this->hasOne(Diagnosis::class);
    }

    /**
     * @return HasMany<Medication, $this>
     */
    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Session chat messages (legacy `ch_messages` / Chatify-style table).
     *
     * @return HasMany<ChMessage, $this>
     */
    public function chMessages(): HasMany
    {
        return $this->hasMany(ChMessage::class, 'appointment_id');
    }
}
