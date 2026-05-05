<?php

use App\Models\Appointment;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Conversation')] class extends Component
{
    public Appointment $appointment;

    public function getSessionTimerProperty(): string
    {
        $start = $this->appointment->actual_start_at;
        if ($start === null) {
            return '00:00';
        }

        $seconds = Carbon::parse($start)->diffInSeconds(now());
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).':'.str_pad((string) $remainingSeconds, 2, '0', STR_PAD_LEFT);
    }
}; ?>

<div class="space-y-6">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'conversation'])

    <div class="rounded-2xl border border-zinc-200/90 bg-white shadow-sm">
        <div class="grid min-h-[34rem] grid-cols-1 lg:grid-cols-12">
            <div class="border-zinc-200 lg:col-span-8 lg:border-e">
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <flux:avatar :name="$appointment->patient_name" circle size="md" />
                            <span class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border border-white bg-emerald-500"></span>
                        </div>
                        <p class="text-base font-semibold text-zinc-900">{{ $appointment->patient_name }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold tabular-nums text-rose-600">{{ $this->sessionTimer }}</span>
                        <flux:button type="button" size="sm" variant="primary" icon="clock">
                            Extend
                        </flux:button>
                        <flux:button type="button" size="sm" variant="ghost" icon="information-circle" />
                    </div>
                </div>

                <div class="relative flex h-[26rem] items-center justify-center bg-zinc-50 px-4">
                    <p class="rounded-full bg-zinc-200 px-4 py-2 text-sm text-zinc-600">Say 'hi' and start messaging</p>
                </div>

                <div class="border-t border-zinc-200 px-4 py-3">
                    <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3 py-2 shadow-sm">
                        <button type="button" class="inline-flex size-8 items-center justify-center rounded-full text-zinc-500 hover:bg-zinc-100">
                            <flux:icon name="plus" class="size-5" />
                        </button>
                        <input
                            type="text"
                            placeholder="Type a message.."
                            class="w-full border-0 bg-transparent text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none focus:ring-0"
                        />
                        <button type="button" class="inline-flex size-8 items-center justify-center rounded-full text-[#3C5CF7] hover:bg-[#3C5CF7]/10">
                            <flux:icon name="paper-airplane" class="size-5" />
                        </button>
                    </div>
                </div>
            </div>

            <aside class="hidden bg-white lg:col-span-4 lg:block">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                    <h3 class="text-lg font-medium text-zinc-900">Patient Details</h3>
                    <button type="button" class="text-[#3C5CF7] hover:text-[#324cc9]">
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>
                <div class="px-6 py-7 text-center">
                    <flux:avatar :name="$appointment->patient_name" circle size="2xl" class="mx-auto" />
                    <p class="mt-4 text-3xl font-semibold text-zinc-900">{{ $appointment->patient_name }}</p>
                    <button type="button" class="mt-5 text-base text-rose-500 hover:text-rose-600">
                        Delete Conversation
                    </button>
                </div>
                <div class="border-t border-zinc-200 px-6 py-4 text-center">
                    <p class="text-sm text-zinc-500">Shared Photos</p>
                    <p class="mt-4 inline-flex rounded-full bg-zinc-100 px-4 py-2 text-xl text-zinc-600">Nothing shared yet</p>
                </div>
            </aside>
        </div>
    </div>
</div>
