<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\Communication;
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

    public bool $acceptInstantAppointment = false;
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

        $this->doctorDurations = $doctor->durations()->pluck('durations.duration')->map(fn ($value) => (string) $value)->all();

        $this->durationPrices = $doctor->durations()
            ->get()
            ->mapWithKeys(fn (Duration $duration): array => [
                (string) $duration->duration => (float) ($duration->pivot?->price ?? 0),
            ])
            ->all();

        $this->acceptInstantAppointment = (bool) $doctor->accept_instant_appointment;
        $this->selectedCommunications = $doctor->communications()->pluck('communications.communication')->all();
        if ($this->selectedCommunications === []) {
            $this->selectedCommunications = ['chat', 'voice_call', 'video_call'];
        }
    }

    public function save(): void
    {
        $this->validate([
            'doctorDurations' => ['required', 'array', 'min:1'],
            'selectedCommunications' => ['required', 'array', 'min:1'],
            'acceptInstantAppointment' => ['boolean'],
        ]);

        foreach ($this->doctorDurations as $duration) {
            $price = $this->durationPrices[(string) $duration] ?? null;
            if ($price === null || $price === '') {
                $this->addError('durationPrices', __('Please provide a price for each selected duration.'));

                return;
            }
            if (! is_numeric($price) || (float) $price < 0) {
                $this->addError('durationPrices', __('Each selected duration must have a valid positive price.'));

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

        session()->flash('duration_saved', __('Duration and prices saved successfully.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('Duration and price') }}</flux:heading>
        <flux:button :href="route('doctor.settings')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <flux:text class="mb-4 text-zinc-600">{{ __('Select session durations and set prices for each selected duration.') }}</flux:text>

        <form wire:submit="save" class="space-y-5">
            <div class="space-y-3">
                @foreach ($durations as $duration)
                    @php
                        $durationKey = (string) $duration->duration;
                        $checked = in_array($durationKey, $doctorDurations, true);
                    @endphp
                    <div class="rounded-xl border border-zinc-200/80 p-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model="doctorDurations"
                                    value="{{ $durationKey }}"
                                    class="h-4 w-4 rounded border-zinc-300 text-[#047857] focus:ring-[#047857]"
                                />
                                <span class="text-sm font-semibold text-zinc-800">
                                    {{ $duration->duration }} {{ __('minutes') }}
                                </span>
                            </label>

                            @if ($checked)
                                <div class="w-full sm:w-52">
                                    <flux:field>
                                        <flux:label>{{ __('Price') }}</flux:label>
                                        <flux:input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            wire:model="durationPrices.{{ $durationKey }}"
                                        />
                                    </flux:field>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:error name="doctorDurations" />
            <flux:error name="durationPrices" />
            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3">
                <flux:text class="mb-3 text-sm font-semibold text-zinc-800">{{ __('Available communication types') }}</flux:text>
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach ($communications as $communication)
                        <label class="inline-flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2">
                            <input
                                type="checkbox"
                                wire:model="selectedCommunications"
                                value="{{ $communication->communication }}"
                                class="h-4 w-4 rounded border-zinc-300 text-[#047857] focus:ring-[#047857]"
                            />
                            <span class="text-sm font-medium text-zinc-800">{{ $communication->title ?: str($communication->communication)->replace('_', ' ')->title() }}</span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="selectedCommunications" />
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3">
                <label class="inline-flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model="acceptInstantAppointment"
                        class="h-4 w-4 rounded border-zinc-300 text-[#047857] focus:ring-[#047857]"
                    />
                    <span class="text-sm font-semibold text-zinc-800">{{ __('Accept instant appointment notifications') }}</span>
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                    {{ __('Save') }}
                </flux:button>
                <flux:button :href="route('doctor.settings')" wire:navigate variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
            </div>

            @if (session('duration_saved'))
                <flux:text class="text-sm font-medium text-emerald-600">{{ session('duration_saved') }}</flux:text>
            @endif
        </form>
    </div>
</div>
