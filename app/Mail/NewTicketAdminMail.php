<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTicketAdminMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.tickets.new_admin_subject', [
                'number' => $this->ticket->ticket_number,
                'app' => config('app.name'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.new-admin',
            with: [
                'ticket' => $this->ticket->loadMissing(['category', 'creator']),
                'adminUrl' => url('/admin/tickets/'.$this->ticket->id),
            ],
        );
    }
}
