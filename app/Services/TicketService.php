<?php

namespace App\Services;

use App\Mail\NewTicketAdminMail;
use App\Models\Admin;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

final class TicketService
{
    public const NOTIFICATION_EMAIL_KEY = 'tickets_notification_email';

    /**
     * @param  User|Doctor  $creator
     */
    public function create(Model $creator, int $categoryId, string $subject, string $message): Ticket
    {
        $audience = $creator instanceof Doctor ? 'doctor' : 'patient';

        $category = TicketCategory::query()
            ->active()
            ->forAudience($audience)
            ->whereKey($categoryId)
            ->first();

        if ($category === null) {
            throw ValidationException::withMessages([
                'categoryId' => __('tickets.invalid_category'),
            ]);
        }

        $ticket = DB::transaction(function () use ($creator, $category, $subject, $message): Ticket {
            return Ticket::query()->create([
                'ticket_number' => $this->generateTicketNumber(),
                'creator_type' => $creator->getMorphClass(),
                'creator_id' => $creator->getKey(),
                'ticket_category_id' => $category->id,
                'subject' => $subject,
                'message' => $message,
                'status' => Ticket::STATUS_OPEN,
            ]);
        });

        $this->notifyAdminByEmail($ticket->fresh(['category', 'creator']));

        return $ticket;
    }

    public function replyAsAdmin(Admin $admin, Ticket $ticket, string $message): TicketReply
    {
        if ($ticket->isClosed()) {
            throw ValidationException::withMessages([
                'message' => __('tickets.already_closed'),
            ]);
        }

        $reply = DB::transaction(function () use ($admin, $ticket, $message): TicketReply {
            $reply = TicketReply::query()->create([
                'ticket_id' => $ticket->id,
                'author_type' => $admin->getMorphClass(),
                'author_id' => $admin->getKey(),
                'message' => $message,
            ]);

            $ticket->update(['status' => Ticket::STATUS_ANSWERED]);

            return $reply;
        });

        $this->notifyCreator(
            $ticket->fresh(['creator']),
            'ticket_replied',
            __('tickets.notifications.replied_title'),
            __('tickets.notifications.replied_message', [
                'number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
            ]),
            $admin,
        );

        return $reply;
    }

    public function close(Admin $admin, Ticket $ticket): Ticket
    {
        if ($ticket->isClosed()) {
            return $ticket;
        }

        $ticket->update([
            'status' => Ticket::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $this->notifyCreator(
            $ticket->fresh(['creator']),
            'ticket_closed',
            __('tickets.notifications.closed_title'),
            __('tickets.notifications.closed_message', [
                'number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
            ]),
            $admin,
        );

        return $ticket;
    }

    public function notificationEmail(): ?string
    {
        return Setting::get(self::NOTIFICATION_EMAIL_KEY);
    }

    public function setNotificationEmail(?string $email): void
    {
        Setting::set(self::NOTIFICATION_EMAIL_KEY, $email);
    }

    public function creatorCanView(Model $creator, Ticket $ticket): bool
    {
        return $ticket->creator_type === $creator->getMorphClass()
            && (int) $ticket->creator_id === (int) $creator->getKey();
    }

    private function generateTicketNumber(): string
    {
        $prefix = 'TKT-'.now()->format('Ymd');

        $latest = Ticket::query()
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('ticket_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%04d', $prefix, $sequence);
    }

    private function notifyAdminByEmail(Ticket $ticket): void
    {
        $email = $this->notificationEmail();

        if (! filled($email)) {
            return;
        }

        try {
            Mail::to($email)->send(new NewTicketAdminMail($ticket));
        } catch (Throwable $exception) {
            Log::warning('Failed to send new ticket admin email.', [
                'ticket_id' => $ticket->id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyCreator(Ticket $ticket, string $type, string $title, string $message, Admin $admin): void
    {
        $creator = $ticket->creator;

        if (! $creator instanceof User && ! $creator instanceof Doctor) {
            return;
        }

        Notification::query()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'userable_type' => $creator->getMorphClass(),
            'userable_id' => $creator->getKey(),
            'senderable_type' => Admin::class,
            'senderable_id' => $admin->id,
            'action' => $this->creatorTicketUrl($ticket),
        ]);
    }

    private function creatorTicketUrl(Ticket $ticket): string
    {
        if ($ticket->creator_type === Doctor::class) {
            return route('doctor.settings.support.show', $ticket);
        }

        return route('patient.support.show', $ticket);
    }
}
