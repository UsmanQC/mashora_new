<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('mail.tickets.new_admin_subject', ['number' => $ticket->ticket_number, 'app' => config('app.name')]) }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <h2 style="color: #132A6E;">{{ __('mail.tickets.new_admin_heading') }}</h2>

    <p>{{ __('mail.tickets.new_admin_intro', ['app' => config('app.name')]) }}</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr>
            <td><strong>{{ __('tickets.number') }}</strong></td>
            <td>{{ $ticket->ticket_number }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('tickets.category') }}</strong></td>
            <td>{{ $ticket->category?->displayName() }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('tickets.subject') }}</strong></td>
            <td>{{ $ticket->subject }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('tickets.creator') }}</strong></td>
            <td>{{ $ticket->creatorDisplayName() }} ({{ $ticket->creatorAudience() }})</td>
        </tr>
    </table>

    <p style="margin-top: 1rem;"><strong>{{ __('tickets.message') }}</strong></p>
    <p style="white-space: pre-wrap; background: #f8fafc; padding: 12px; border-radius: 8px;">{{ $ticket->message }}</p>

    <p style="margin-top: 1.5rem;">
        <a href="{{ $adminUrl }}" style="display: inline-block; background: #132A6E; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none;">
            {{ __('mail.tickets.view_in_admin') }}
        </a>
    </p>
</body>
</html>
