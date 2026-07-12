@props([
    'expiresAtIso',
    'variant' => 'page',
])

<div
    x-data="paymentDeadlineTimer(@js($expiresAtIso), @js(__('patient.scheduled_appointment.payment_expired')))"
    x-init="start()"
    @class([
        'rounded-2xl px-4 py-3',
        'border border-violet-200 bg-gradient-to-r from-violet-50 to-indigo-50' => $variant === 'page',
        'bg-gradient-to-r from-violet-600 to-indigo-600 text-white' => $variant === 'card',
    ])
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p @class([
                'text-sm font-semibold leading-snug',
                'text-violet-950' => $variant === 'page',
                'text-white' => $variant === 'card',
            ])>
                {{ __('patient.scheduled_appointment.pay_confirm_urgency') }}
            </p>
            <p @class([
                'mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs',
                'text-violet-800/90' => $variant === 'page',
                'text-violet-100' => $variant === 'card',
            ])>
                <span>{{ __('patient.scheduled_appointment.pay_within') }}:</span>
                <span @class([
                    'font-bold tabular-nums',
                    'text-violet-700' => $variant === 'page',
                    'text-white' => $variant === 'card',
                ]) x-text="label"></span>
            </p>
        </div>
        <flux:icon
            name="clock"
            variant="mini"
            @class([
                'size-5 shrink-0',
                'text-violet-700' => $variant === 'page',
                'text-white/90' => $variant === 'card',
            ])
        />
    </div>
</div>
