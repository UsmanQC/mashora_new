<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TemporaryAppointment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'doctor_id',
        'appointment_id',
        'parent_id',
        'is_follow_up',
        'patient_confirmed_at',
        'scheduled_at',
        'appointment_date',
        'start_time',
        'end_time',
        'duration',
        'extend_at',
        'appointment_for',
        'patient_name',
        'patient_email',
        'patient_phone',
        'patient_notes',
        'communications',
        'amount',
        'discount',
        'tax',
        'total',
        'wallet_amount',
        'appointment_type',
        'instant_counseling',
        'payment_session_id',
        'payment_invoice_id',
        'payment_invoice_url',
        'payment_response',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'appointment_date' => 'date',
            'extend_at' => 'datetime',
            'communications' => 'array',
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'wallet_amount' => 'decimal:2',
            'is_follow_up' => 'boolean',
            'patient_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TemporaryAppointment $model): void {
            if ($model->id === null || $model->id === '') {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * @param  Builder<TemporaryAppointment>  $query
     * @return Builder<TemporaryAppointment>
     */
    public function scopeAvailableForFiveMin($query, int $doctorId, string $startTime, string $endTime, ?string $date = null)
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
            ->when(auth()->check(), fn ($q) => $q->where('user_id', '!=', auth()->id()))
            ->where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->where('created_at', '>=', Carbon::now()->subMinutes(5));
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function parentAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'parent_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
