<?php

namespace App\Models;

use App\Services\PatientAppointmentNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;

class Notification extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'title',
        'message',
        'read_at',
        'userable_type',
        'userable_id',
        'senderable_type',
        'senderable_id',
        'notifiable_type',
        'notifiable_id',
        'action',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    public function displayTitle(): string
    {
        $title = (string) ($this->title ?? '');

        if ($title === '' || ! Lang::has($title)) {
            return $title;
        }

        return __($title);
    }

    public function displayMessage(): string
    {
        $message = (string) ($this->message ?? '');

        if ($message === '' || ! Lang::has($message)) {
            return $message;
        }

        if ($this->type === 'appointment_missed') {
            $appointment = $this->relatedMissedAppointment();

            if ($appointment !== null && $appointment->doctor instanceof Doctor) {
                return app(PatientAppointmentNotifier::class)
                    ->missedNotificationCopy($appointment, $appointment->doctor)['message'];
            }
        }

        return __($message);
    }

    public function repairStoredCopy(): bool
    {
        if ($this->type !== 'appointment_missed') {
            return false;
        }

        $appointment = $this->relatedMissedAppointment();

        if ($appointment === null || ! $appointment->doctor instanceof Doctor) {
            return false;
        }

        ['title' => $title, 'message' => $message] = app(PatientAppointmentNotifier::class)
            ->missedNotificationCopy($appointment, $appointment->doctor);

        if ($this->title === $title && $this->message === $message) {
            return false;
        }

        $this->forceFill([
            'title' => $title,
            'message' => $message,
        ])->save();

        return true;
    }

    private function relatedMissedAppointment(): ?Appointment
    {
        if ($this->type !== 'appointment_missed' || $this->senderable_type !== Doctor::class) {
            return null;
        }

        return Appointment::query()
            ->with('doctor')
            ->where('user_id', $this->userable_id)
            ->where('doctor_id', $this->senderable_id)
            ->where('status', 'not_attended')
            ->where('cancel_status', 'doctor_missed')
            ->orderByDesc('updated_at')
            ->first();
    }
}
