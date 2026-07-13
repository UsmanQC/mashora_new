<?php

use App\Models\Doctor;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Working hours')] class extends Component
{
    /** @var list<string> */
    public array $daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    /** @var list<string> */
    public array $availabilities = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'saturday'];

    /**
     * @var array<string, array<int, array{start_time: string, end_time: string}>>
     */
    public array $workingHours = [];

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function mount(): void
    {
        $doctor = $this->doctor();

        if ($doctor->workingDays()->exists()) {
            /** @var array<int, WorkingDay> $workingDays */
            $workingDays = $doctor->workingDays()->with('workingHours')->get()->all();

            $this->availabilities = collect($workingDays)
                ->pluck('day_of_week')
                ->filter()
                ->values()
                ->all();

            foreach ($workingDays as $workingDay) {
                if (! $workingDay->day_of_week) {
                    continue;
                }

                $this->workingHours[$workingDay->day_of_week] = $workingDay->workingHours
                    ->map(fn ($slot): array => [
                        'start_time' => (string) $slot->start_time,
                        'end_time' => (string) $slot->end_time,
                    ])
                    ->values()
                    ->all();
            }
        } else {
            $this->seedDefaultSlots();
        }
    }

    private function seedDefaultSlots(): void
    {
        foreach ($this->availabilities as $dayOfWeek) {
            $this->workingHours[$dayOfWeek] = [[
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
            ]];
        }
    }

    public function updatedAvailabilities(): void
    {
        foreach ($this->availabilities as $dayOfWeek) {
            if (! isset($this->workingHours[$dayOfWeek]) || count($this->workingHours[$dayOfWeek]) === 0) {
                $this->workingHours[$dayOfWeek][] = [
                    'start_time' => '10:00:00',
                    'end_time' => '14:00:00',
                ];
            }
        }
    }

    public function addSlot(string $dayOfWeek): void
    {
        $lastEnd = '14:00:00';

        if (! empty($this->workingHours[$dayOfWeek])) {
            $last = end($this->workingHours[$dayOfWeek]);
            $lastEnd = (string) ($last['end_time'] ?? '14:00:00');
        }

        $start = Carbon::createFromTimeString($lastEnd)->format('H:i:s');
        $end = Carbon::createFromTimeString($start)->addHour()->format('H:i:s');

        $this->workingHours[$dayOfWeek][] = [
            'start_time' => $start,
            'end_time' => $end,
        ];
    }

    public function removeSlot(string $dayOfWeek, int $index): void
    {
        if (! isset($this->workingHours[$dayOfWeek][$index])) {
            return;
        }

        unset($this->workingHours[$dayOfWeek][$index]);
        $this->workingHours[$dayOfWeek] = array_values($this->workingHours[$dayOfWeek]);
    }

    public function goBack(): void
    {
        $this->redirect(route('doctor.register.duration'), navigate: true);
    }

    public function finish(): void
    {
        $this->validate([
            'availabilities' => ['required', 'array', 'min:1'],
            'workingHours' => ['required', 'array'],
            'workingHours.*' => ['array'],
            'workingHours.*.*.start_time' => ['required', 'date_format:H:i:s'],
            'workingHours.*.*.end_time' => ['required', 'date_format:H:i:s'],
        ]);

        foreach ($this->availabilities as $day) {
            foreach ($this->workingHours[$day] ?? [] as $index => $slot) {
                if (($slot['end_time'] ?? '') <= ($slot['start_time'] ?? '')) {
                    $this->addError("workingHours.$day.$index.end_time", __('End time must be after start time.'));

                    return;
                }
            }
        }

        $doctor = $this->doctor();

        DB::transaction(function () use ($doctor): void {
            $doctor->workingDays()
                ->whereNotIn('day_of_week', $this->availabilities)
                ->delete();

            foreach ($this->availabilities as $dayOfWeek) {
                $workingDay = $doctor->workingDays()->updateOrCreate(
                    ['doctor_id' => $doctor->id, 'day_of_week' => $dayOfWeek],
                    ['is_working' => true]
                );

                $slots = collect($this->workingHours[$dayOfWeek] ?? [])
                    ->filter(fn (array $slot): bool => filled($slot['start_time'] ?? null) && filled($slot['end_time'] ?? null))
                    ->values();

                $workingDay->workingHours()->delete();

                foreach ($slots as $slot) {
                    $workingDay->workingHours()->create([
                        'start_time' => (string) $slot['start_time'],
                        'end_time' => (string) $slot['end_time'],
                    ]);
                }
            }
        });

        $doctor->update(['profile_completed' => true]);

        $this->redirect(route('doctor.account-status'), navigate: true);
    }
}; ?>

<div class="doctor-onboarding-basic mx-auto max-w-lg space-y-5 pb-10 sm:max-w-xl sm:space-y-8 sm:pb-8">
    @include('partials.doctor-onboarding-header', [
        'current' => 6,
        'total' => 6,
        'title' => __('doctor.auth.working_hours_title'),
        'subtitle' => __('doctor.auth.working_hours_subtitle'),
    ])

    <form wire:submit="finish" class="doctor-emerald-accent space-y-4">
        <flux:checkbox.group wire:model.live="availabilities" class="space-y-4">
            @foreach ($daysOfWeek as $dayOfWeek)
                <div class="rounded-xl border border-zinc-200/80 bg-white p-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <label class="group inline-flex items-center gap-3">
                            <flux:checkbox value="{{ $dayOfWeek }}" class="shrink-0" />
                            <span class="doctor-working-day-label text-sm font-semibold capitalize text-zinc-800 group-has-[[data-checked]]:text-mashora-brand">
                                {{ __($dayOfWeek) }}
                            </span>
                        </label>

                        <div class="flex w-full flex-col gap-2 lg:max-w-2xl">
                            @if (in_array($dayOfWeek, $availabilities, true))
                                @foreach (($workingHours[$dayOfWeek] ?? []) as $slotIndex => $slot)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input
                                            type="time"
                                            step="1"
                                            wire:model="workingHours.{{ $dayOfWeek }}.{{ $slotIndex }}.start_time"
                                            class="doctor-working-hours-time-input rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-slate-900"
                                        />
                                        <span class="text-zinc-500">-</span>
                                        <input
                                            type="time"
                                            step="1"
                                            wire:model="workingHours.{{ $dayOfWeek }}.{{ $slotIndex }}.end_time"
                                            class="doctor-working-hours-time-input rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-slate-900"
                                        />

                                        @if ($loop->first)
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="addSlot('{{ $dayOfWeek }}')" icon="plus">
                                                {{ __('Add') }}
                                            </flux:button>
                                        @else
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="removeSlot('{{ $dayOfWeek }}', {{ $slotIndex }})" icon="trash" class="!text-rose-600">
                                                {{ __('Remove') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </flux:checkbox.group>

        <flux:error name="availabilities" />
        <flux:error name="workingHours" />

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
            <flux:button type="button" variant="ghost" wire:click="goBack" class="order-2 sm:order-1">
                {{ __('doctor.auth.back') }}
            </flux:button>
            <flux:button
                class="order-1 w-full !bg-[#10B981] !text-white hover:!brightness-95 sm:order-2 sm:w-auto"
                type="submit"
                variant="primary"
            >
                {{ __('doctor.auth.save_continue') }}
            </flux:button>
        </div>
    </form>
</div>
