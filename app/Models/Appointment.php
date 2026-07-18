<?php

namespace App\Models;

use App\Services\FollowUpAppointmentService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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

    /**
     * @var list<string>
     */
    public const UPCOMING_FOLLOW_UP_STATUSES = [
        'pending_follow_up',
        'new',
        'rescheduled',
        'in_process',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'scheduled_at' => 'datetime',
            'prescription_not_needed' => 'boolean',
            'is_follow_up' => 'boolean',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'extend_at' => 'datetime',
            'session_start_requested_at' => 'datetime',
            'session_start_approved_at' => 'datetime',
            'patient_confirmed_at' => 'datetime',
            'payment_expires_at' => 'datetime',
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
        'parent_id',
        'patient_confirmed_at',
        'payment_expires_at',
        'is_follow_up',
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
        'session_start_requested_at',
        'session_start_approved_at',
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

    public function sessionStartsAt(): ?Carbon
    {
        if ($this->appointment_date !== null && filled($this->start_time)) {
            try {
                $datePart = $this->appointment_date instanceof Carbon
                    ? $this->appointment_date->format('Y-m-d')
                    : Carbon::parse($this->appointment_date)->format('Y-m-d');

                return Carbon::parse($datePart.' '.(string) $this->start_time, config('app.timezone'));
            } catch (\Throwable) {
                // Fall through to scheduled_at when date/time parts cannot be parsed.
            }
        }

        if ($this->scheduled_at !== null) {
            return Carbon::parse($this->scheduled_at)->timezone(config('app.timezone'));
        }

        return null;
    }

    public function isSessionStartDue(?CarbonInterface $now = null): bool
    {
        $startsAt = $this->sessionStartsAt();

        if ($startsAt === null) {
            return true;
        }

        $now ??= now()->timezone(config('app.timezone'));

        return $now->greaterThanOrEqualTo($startsAt->copy()->subHour());
    }

    public function isScheduledSessionTimeReached(?CarbonInterface $now = null): bool
    {
        $startsAt = $this->sessionStartsAt();

        if ($startsAt === null) {
            return true;
        }

        $now ??= now()->timezone(config('app.timezone'));

        return $now->greaterThanOrEqualTo($startsAt);
    }

    public function hasSessionStartRequest(): bool
    {
        return $this->session_start_requested_at !== null;
    }

    public function isSessionStartRequestPending(): bool
    {
        return $this->session_start_requested_at !== null
            && $this->session_start_approved_at === null;
    }

    public function isSessionStartApproved(): bool
    {
        return $this->session_start_approved_at !== null;
    }

    public function sessionEndsAt(): ?Carbon
    {
        if ($this->appointment_date === null) {
            return null;
        }

        try {
            $datePart = $this->appointment_date instanceof Carbon
                ? $this->appointment_date->format('Y-m-d')
                : Carbon::parse($this->appointment_date)->format('Y-m-d');

            if (filled($this->end_time)) {
                return Carbon::parse($datePart.' '.(string) $this->end_time, config('app.timezone'));
            }

            $startsAt = $this->sessionStartsAt();

            if ($startsAt === null) {
                return null;
            }

            $durationMinutes = $this->effectiveSessionDurationMinutes();

            return $startsAt->copy()->addMinutes($durationMinutes);
        } catch (\Throwable) {
            return null;
        }
    }

    public function effectiveSessionDurationMinutes(): int
    {
        if ($this->is_follow_up) {
            return FollowUpAppointmentService::sessionDurationMinutes();
        }

        return max(1, (int) ($this->duration ?? 0));
    }

    public function isPendingFollowUp(): bool
    {
        return $this->status === 'pending_follow_up';
    }

    public function requiresPatientPayment(): bool
    {
        return $this->isPendingFollowUp()
            && ! $this->is_follow_up
            && (float) $this->total > 0
            && $this->patient_confirmed_at === null;
    }

    public function isPaymentExpired(): bool
    {
        if ($this->payment_expires_at === null) {
            return false;
        }

        return $this->payment_expires_at->isPast();
    }

    public function paymentExpiresAtIso(): ?string
    {
        return $this->payment_expires_at?->toIso8601String();
    }

    public function isPatientPaymentMissed(): bool
    {
        return $this->status === 'cancelled'
            && $this->cancel_status === 'patient_payment_missed';
    }

    public function isUpcomingFollowUp(): bool
    {
        return $this->is_follow_up
            && in_array((string) $this->status, self::UPCOMING_FOLLOW_UP_STATUSES, true);
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeUpcomingFollowUp(Builder $query): Builder
    {
        return $query
            ->where('is_follow_up', true)
            ->whereIn('status', self::UPCOMING_FOLLOW_UP_STATUSES);
    }

    public function chatOpenUntil(): CarbonInterface
    {
        $reference = $this;

        if ($this->is_follow_up) {
            $this->loadMissing('parentAppointment');

            if ($this->parentAppointment instanceof self) {
                $reference = $this->parentAppointment;
            }
        }

        $timezone = config('app.timezone');
        $hours = max(1, (int) config('appointments.post_session_chat_window_hours', 24));

        $sessionEndedAt = $reference->extend_at
            ?? $reference->sessionEndsAt()
            ?? $reference->actual_start_at
            ?? $reference->sessionStartsAt();

        if ($sessionEndedAt === null) {
            return now($timezone)->addHours($hours);
        }

        return $sessionEndedAt->copy()->timezone($timezone)->addHours($hours);
    }

    public function isChatOpen(?CarbonInterface $now = null): bool
    {
        $now ??= now(config('app.timezone'));

        if ((string) $this->status === 'in_process') {
            return true;
        }

        if ($this->is_follow_up && in_array((string) $this->status, ['new', 'completed'], true)) {
            if ((string) $this->status === 'new' && $this->patient_confirmed_at === null) {
                return false;
            }

            return $now->lessThanOrEqualTo($this->chatOpenUntil());
        }

        if (in_array((string) $this->status, ['new', 'rescheduled'], true)) {
            if ((bool) config('appointments.relaxed_session_limits', false)) {
                return true;
            }

            return $this->isSessionStartDue($now);
        }

        if ((string) $this->status !== 'completed') {
            return false;
        }

        return $now->lessThanOrEqualTo($this->chatOpenUntil());
    }

    public function isDoctorChatOpen(?CarbonInterface $now = null): bool
    {
        return $this->isChatOpen($now);
    }

    public function allowsPatientCalls(): bool
    {
        if ($this->is_follow_up) {
            return (bool) config('appointments.follow_up_allows_calls', false);
        }

        return true;
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function parentAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'parent_id');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(Appointment::class, 'parent_id');
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
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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

    /**
     * @return HasMany<AppointmentRefundRequest, $this>
     */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(AppointmentRefundRequest::class);
    }

    /**
     * Checkout booking row that created this appointment (holds MyFatoorah invoice ids).
     *
     * @return HasOne<TemporaryAppointment, $this>
     */
    public function temporaryAppointment(): HasOne
    {
        return $this->hasOne(TemporaryAppointment::class, 'appointment_id');
    }

    /**
     * MyFatoorah invoice id from the appointment, or from the linked temporary booking.
     */
    public function resolvedPaymentInvoiceId(): ?string
    {
        $invoiceId = trim((string) ($this->payment_invoice_id ?? ''));

        if ($invoiceId !== '') {
            return $invoiceId;
        }

        $this->loadMissing('temporaryAppointment');

        $temporaryInvoiceId = trim((string) ($this->temporaryAppointment?->payment_invoice_id ?? ''));

        return $temporaryInvoiceId !== '' ? $temporaryInvoiceId : null;
    }

    public function hasPaymentAccountRefundSource(): bool
    {
        return filled($this->resolvedPaymentInvoiceId());
    }

    public function isDoctorMissed(): bool
    {
        return $this->status === 'not_attended'
            && $this->cancel_status === 'doctor_missed';
    }

    public function isPatientRefunded(): bool
    {
        return $this->status === 'cancelled'
            && $this->cancel_status === 'patient_refunded';
    }

    public function hasRefundRequest(): bool
    {
        return $this->refundRequests()->exists();
    }

    public function hasOpenRefundRequest(): bool
    {
        return $this->refundRequests()
            ->whereIn('status', ['pending_review', 'approved'])
            ->exists();
    }
}
