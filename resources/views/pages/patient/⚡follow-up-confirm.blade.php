<?php

use App\Models\Appointment;
use App\Models\User;
use App\Services\FollowUpAppointmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Follow-up appointment')] class extends Component
{
    public Appointment $appointment;

    public function mount(Appointment $appointment): void
    {
        $user = Auth::user();
        if (! $user instanceof User || (int) $appointment->user_id !== (int) $user->id) {
            abort(403);
        }

        if (! $appointment->isPendingFollowUp()) {
            abort(404);
        }

        $this->appointment = $appointment->load('doctor', 'parentAppointment');
    }

    public function formattedDate(): string
    {
        try {
            return Carbon::parse($this->appointment->appointment_date)
                ->locale(app()->getLocale())
                ->translatedFormat('d M Y');
        } catch (\Throwable) {
            return (string) $this->appointment->appointment_date;
        }
    }

    public function formattedTime(): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', (string) $this->appointment->start_time)
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return (string) $this->appointment->start_time;
        }
    }

    public function doctorName(): string
    {
        return $this->appointment->doctor?->displayName() ?? '';
    }

    public function confirmAndPay(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $confirmed = app(FollowUpAppointmentService::class)->confirm($this->appointment, $user);

        $this->redirect(route('patient.follow-up.pay', $confirmed));
    }
}; ?>

<div class="mx-auto max-w-xl space-y-6 px-4 py-8">
    <div>
        <flux:heading size="xl" class="font-semibold text-[#193ADB]">{{ __('patient.follow_up.title') }}</flux:heading>
        <flux:text class="mt-2 text-zinc-600">{{ __('patient.follow_up.subtitle') }}</flux:text>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <flux:badge color="sky" class="mb-4">{{ __('patient.follow_up.badge') }}</flux:badge>

        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-500">{{ __('patient.follow_up.doctor') }}</dt>
                <dd class="font-semibold text-zinc-900">{{ $this->doctorName() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-500">{{ __('patient.follow_up.date') }}</dt>
                <dd class="font-semibold text-zinc-900">{{ $this->formattedDate() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-500">{{ __('patient.follow_up.time') }}</dt>
                <dd class="font-semibold text-zinc-900">{{ $this->formattedTime() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-500">{{ __('patient.follow_up.duration') }}</dt>
                <dd class="font-semibold text-zinc-900">{{ $appointment->duration }} {{ __('minutes') }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-t border-zinc-100 pt-3">
                <dt class="text-zinc-500">{{ __('patient.follow_up.amount') }}</dt>
                <dd class="text-lg font-bold text-[#193ADB]">
                    {{ number_format((float) $appointment->total, 2) }} {{ config('currency.sa_riyal_symbol') }}
                </dd>
            </div>
        </dl>

        <flux:text class="mt-4 text-sm text-zinc-600">{{ __('patient.follow_up.confirm_hint') }}</flux:text>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <flux:button
                wire:click="confirmAndPay"
                variant="primary"
                class="w-full !bg-[#193ADB] !text-white hover:!brightness-95 sm:flex-1"
            >
                {{ __('patient.follow_up.confirm_and_pay') }}
            </flux:button>
            <flux:button :href="route('patient.appointments')" wire:navigate variant="ghost" class="w-full sm:w-auto">
                {{ __('patient.follow_up.later') }}
            </flux:button>
        </div>
    </div>
</div>
