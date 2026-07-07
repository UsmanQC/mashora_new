<?php

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('This week')] class extends Component
{
    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function initialsFor(?string $name): string
    {
        if (! filled($name)) {
            return '?';
        }

        return \Illuminate\Support\Str::of((string) $name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => \Illuminate\Support\Str::substr($word, 0, 1))
            ->implode('');
    }

    public function statusBadgeColorFor(Appointment $appointment): string
    {
        return match ($appointment->status) {
            'new' => 'sky',
            'in_process' => 'amber',
            'completed' => 'emerald',
            'cancelled', 'not_attended' => 'rose',
            'rescheduled' => 'indigo',
            'pending_follow_up' => 'violet',
            default => 'zinc',
        };
    }

    #[Computed]
    public function weekRangeLabel(): string
    {
        $timezone = config('app.timezone');
        $locale = app()->getLocale();
        $start = now($timezone)->startOfWeek(Carbon::SUNDAY)->locale($locale);
        $end = $start->copy()->addDays(6);

        return $start->translatedFormat('d M').' - '.$end->translatedFormat('d M');
    }

    /**
     * @return list<array{iso: string, label: string, day_num: int, is_today: bool, appointments: Collection<int, Appointment>}>
     */
    #[Computed]
    public function weekDays(): array
    {
        $timezone = config('app.timezone');
        $locale = app()->getLocale();
        $today = now($timezone);
        $startOfWeek = $today->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        $appointmentsByDay = $this->doctor()->appointments()
            ->whereBetween('appointment_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Appointment $appointment): string => Carbon::parse($appointment->appointment_date)->toDateString());

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i)->locale($locale);
            $iso = $date->toDateString();

            $days[] = [
                'iso' => $iso,
                'label' => $date->translatedFormat('D'),
                'day_num' => $date->day,
                'is_today' => $iso === $today->toDateString(),
                'appointments' => $appointmentsByDay->get($iso, collect()),
            ];
        }

        return $days;
    }
}; ?>

<div class="relative w-full">
    @include('partials.doctor-luxury-appointments-week-mobile')

    <div class="hidden space-y-6 lg:block">
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.appointments_week.title') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600">{{ $this->weekRangeLabel }}</flux:text>
            </div>
            <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
        </div>

        <div class="space-y-4">
            @foreach ($this->weekDays as $day)
                <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <flux:heading size="sm" @class(['font-semibold', 'text-[#047857]' => $day['is_today'], 'text-zinc-900' => ! $day['is_today']])>
                            {{ $day['label'] }} · {{ $day['day_num'] }}
                            @if ($day['is_today'])
                                <span class="ms-1 rounded-full bg-[#10B981]/10 px-2 py-0.5 text-[11px] font-semibold text-[#047857]">{{ __('doctor.appointments_week.today') }}</span>
                            @endif
                        </flux:heading>
                        <span class="text-xs font-semibold text-zinc-400">{{ $day['appointments']->count() }}</span>
                    </div>

                    @if ($day['appointments']->isEmpty())
                        <p class="text-sm text-zinc-400">{{ __('doctor.appointments_week.no_sessions') }}</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($day['appointments'] as $appointment)
                                <a
                                    href="{{ route('doctor.appointments.conversation', $appointment) }}"
                                    wire:navigate
                                    class="flex items-center gap-3 rounded-xl border border-zinc-200/80 p-3 transition hover:border-[#10B981]/35 hover:shadow-sm"
                                >
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[#10B981]/10 text-xs font-bold text-[#047857]">
                                        {{ $this->initialsFor($appointment->patient_name) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-zinc-900">{{ $appointment->patient_name }}</p>
                                        <p class="text-xs text-zinc-500">{{ $appointment->formattedSessionStart() ?: '—' }}</p>
                                    </div>
                                    <flux:badge :color="$this->statusBadgeColorFor($appointment)" size="sm">
                                        {{ __('doctor.appointment_status.'.$appointment->status) }}
                                    </flux:badge>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
