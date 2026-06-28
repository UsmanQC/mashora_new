<?php

use App\Models\Communication;
use App\Models\Doctor;
use App\Models\Duration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Duration and price')] class extends Component
{
    /**
     * @var array<int, Duration>
     */
    public array $durations = [];

    /**
     * @var list<int|string>
     */
    public array $doctorDurations = [];

    /**
     * @var array<int|string, float|int|string>
     */
    public array $durationPrices = [];

    public bool $acceptInstantAppointment = true;

    /**
     * @var array<int, Communication>
     */
    public array $communications = [];

    /**
     * @var list<string>
     */
    public array $selectedCommunications = [];

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

        $this->durations = Duration::query()->orderBy('duration')->get()->all();

        Communication::query()->upsert([
            ['communication' => 'chat', 'title' => 'Chat'],
            ['communication' => 'voice_call', 'title' => 'Voice call'],
            ['communication' => 'video_call', 'title' => 'Video call'],
        ], ['communication'], ['title']);

        $this->communications = Communication::query()->orderBy('title')->get()->all();

        $this->doctorDurations = $doctor->durations()->exists()
            ? $doctor->durations()->pluck('durations.duration')->map(fn ($value) => (string) $value)->all()
            : ['15', '30', '45', '60'];

        $this->durationPrices = $doctor->durations()->exists()
            ? $doctor->durations()
                ->get()
                ->mapWithKeys(fn (Duration $duration): array => [
                    (string) $duration->duration => (float) ($duration->pivot?->price ?? 0),
                ])
                ->all()
            : [];

        $this->selectedCommunications = $doctor->communications()->exists()
            ? $doctor->communications()->pluck('communications.communication')->all()
            : ['chat', 'voice_call', 'video_call'];

        $this->acceptInstantAppointment = $doctor->accept_instant_appointment !== false;
    }

    public function updatedDurationPrices(): void
    {
        if (count($this->durationPrices) === 1 && isset($this->durationPrices['15']) && (float) $this->durationPrices['15'] > 0) {
            foreach ($this->durations as $duration) {
                if ((int) $duration->duration === 15) {
                    continue;
                }

                $this->durationPrices[(string) $duration->duration] = (float) $this->durationPrices['15'] * ((int) $duration->duration / 15);
            }
        }
    }

    public function goBack(): void
    {
        $this->redirect(route('doctor.register.basic.info'), navigate: true);
    }

    public function save(): void
    {
        $this->validate([
            'doctorDurations' => ['required', 'array', 'min:1'],
            'selectedCommunications' => ['required', 'array', 'min:1'],
            'acceptInstantAppointment' => ['boolean'],
        ]);

        if (! in_array('chat', $this->selectedCommunications, true)) {
            $this->addError('selectedCommunications', __('doctor.auth.chat_required'));

            return;
        }

        foreach ($this->doctorDurations as $duration) {
            $price = $this->durationPrices[(string) $duration] ?? null;
            if ($price === null || $price === '') {
                $this->addError('durationPrices', __('doctor.auth.duration_price_required'));

                return;
            }
            if (! is_numeric($price) || (float) $price < 0) {
                $this->addError('durationPrices', __('doctor.auth.duration_price_invalid'));

                return;
            }
        }

        $doctor = $this->doctor();

        /** @var array<int|string, array{price: float}> $sync */
        $sync = Arr::mapWithKeys($this->doctorDurations, fn ($duration): array => [
            (int) $duration => ['price' => (float) $this->durationPrices[(string) $duration]],
        ]);

        $doctor->durations()->sync($sync);
        $doctor->communications()->sync($this->selectedCommunications);
        $doctor->update(['accept_instant_appointment' => $this->acceptInstantAppointment]);

        $this->redirect(route('doctor.register.working-hours'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-xl space-y-8">
    <div class="space-y-2">
        <flux:text class="text-sm font-medium text-zinc-500">
            {{ __('doctor.auth.onboarding_progress', ['current' => 4, 'total' => 5]) }}
        </flux:text>
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.duration_title') }}</flux:heading>
        <flux:text class="text-zinc-600">{{ __('doctor.auth.duration_subtitle') }}</flux:text>
    </div>

    <form wire:submit="save" class="doctor-emerald-accent space-y-5">
        <flux:checkbox.group wire:model.live="doctorDurations" class="space-y-3">
            @foreach ($durations as $duration)
                @php
                    $durationKey = (string) $duration->duration;
                    $checked = in_array($durationKey, $doctorDurations, true);
                @endphp
                <div class="rounded-xl border border-zinc-200/80 bg-white p-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <label class="inline-flex items-center gap-3">
                            <flux:checkbox value="{{ $durationKey }}" class="shrink-0" />
                            <span class="text-sm font-semibold text-zinc-800">
                                {{ __('doctor.auth.duration_minutes', ['minutes' => $duration->duration]) }}
                            </span>
                        </label>

                        @if ($checked)
                            <div class="w-full sm:w-52">
                                <flux:field>
                                    <flux:label>{{ __('Price') }} ({{ config('currency.sa_riyal_symbol') }})</flux:label>
                                    <flux:input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.blur="durationPrices.{{ $durationKey }}"
                                    />
                                </flux:field>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </flux:checkbox.group>

        <flux:error name="doctorDurations" />
        <flux:error name="durationPrices" />

        <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3">
            <flux:text class="mb-3 text-sm font-semibold text-zinc-800">{{ __('doctor.auth.appointment_types') }}</flux:text>
            <flux:checkbox.group wire:model.live="selectedCommunications" class="grid gap-2 sm:grid-cols-3">
                @foreach ($communications as $communication)
                    <label class="inline-flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2">
                        <flux:checkbox value="{{ $communication->communication }}" class="shrink-0" />
                        <span class="text-sm font-medium text-zinc-800">{{ $communication->title ?: str($communication->communication)->replace('_', ' ')->title() }}</span>
                    </label>
                @endforeach
            </flux:checkbox.group>
            <flux:error name="selectedCommunications" />
        </div>

        <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3">
            <label class="inline-flex items-center gap-3">
                <flux:checkbox wire:model.live="acceptInstantAppointment" class="shrink-0" />
                <span class="text-sm font-semibold text-zinc-800">{{ __('doctor.auth.accept_instant_appointment') }}</span>
            </label>
            <flux:text class="mt-1 text-xs text-zinc-500">{{ __('doctor.auth.accept_instant_appointment_hint') }}</flux:text>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
            <flux:button type="button" variant="ghost" wire:click="goBack" class="order-2 sm:order-1">
                {{ __('doctor.auth.back') }}
            </flux:button>
            <flux:button
                class="order-1 w-full !bg-[#10B981] !text-white hover:!brightness-95 sm:order-2 sm:w-auto"
                type="submit"
                variant="primary"
            >
                {{ __('doctor.auth.continue') }}
            </flux:button>
        </div>
    </form>
</div>
