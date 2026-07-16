<?php

namespace App\Mail;

use App\Models\Doctor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DoctorAccountApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Doctor $doctor) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.doctor_approved.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.doctor.account-approved',
            with: [
                'doctor' => $this->doctor,
                'loginUrl' => route('doctor.login'),
                'logoUrl' => asset('images/awan_logo.png'),
            ],
        );
    }
}
