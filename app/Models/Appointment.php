<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

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
        'appointment_type',
        'instant_counseling',
        'status',
        'payment_session_id',
        'payment_invoice_id',
        'payment_invoice_url',
        'refund_payment_invoice_id',
        'refund_payment_response',
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

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
