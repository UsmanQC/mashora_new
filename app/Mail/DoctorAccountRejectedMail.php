<?php

namespace App\Mail;

use App\Models\Doctor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DoctorAccountRejectedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Doctor $doctor, public ?string $reason = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.doctor_rejected.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.doctor.account-rejected',
            with: [
                'doctor' => $this->doctor,
                'reason' => $this->reason,
                'loginUrl' => route('doctor.login'),
            ],
        );
    }
}
