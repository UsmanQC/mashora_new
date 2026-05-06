<?php

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::doctor')] #[Title('Appointments')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'all';

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return [
            'all' => __('All'),
            'in_process' => __('In Process'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            'rescheduled' => __('Rescheduled'),
            'not_attended' => __('No attended'),
        ];
    }

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    /**
     * @return Builder<Appointment>
     */
    protected function baseAppointmentsQuery(): Builder
    {
        return Appointment::query()
            ->where('doctor_id', $this->doctor()->id);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function getAppointmentsProperty(): LengthAwarePaginator
    {
        return $this->baseAppointmentsQuery()
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(12);
    }

    /**
     * @return Collection<string, int>
     */
    public function getStatusCountsProperty(): Collection
    {
        $counts = $this->baseAppointmentsQuery()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        return collect($this->statusOptions())
            ->except('all')
            ->mapWithKeys(fn ($_label, $status): array => [$status => (int) ($counts[$status] ?? 0)]);
    }

    public function getMaxStatusCountProperty(): int
    {
        return max(1, $this->statusCounts->max() ?? 0);
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold tracking-tight text-zinc-900">{{ __('doctor.appointments.title') }}</flux:heading>
        <span class="inline-flex items-center rounded-full bg-[#3C5CF7]/10 px-3 py-1 text-xs font-semibold text-[#2f49ca]">
            {{ trans_choice(':count records', $this->appointments->total(), ['count' => $this->appointments->total()]) }}
        </span>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm sm:p-5">
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($this->statusOptions() as $key => $label)
                <flux:button
                    type="button"
                    wire:click="$set('status', '{{ $key }}')"
                    size="sm"
                    :variant="$status === $key ? 'primary' : 'outline'"
                    class="{{ $status === $key ? '!bg-[#132A6E] !text-white' : '!border-zinc-200 text-zinc-700' }}"
                >
                    {{ $label }}
                </flux:button>
            @endforeach
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($this->statusOptions() as $key => $label)
                @continue($key === 'all')
                @php
                    $count = $this->statusCounts[$key] ?? 0;
                    $width = (int) round(($count / $this->maxStatusCount) * 100);
                    $isActive = $status === $key;
                @endphp
                <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/60 p-3">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="truncate text-xs font-semibold uppercase text-zinc-500">{{ $label }}</span>
                        <span class="text-sm font-bold text-zinc-900">{{ $count }}</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-zinc-200/70">
                        <div
                            class="h-full rounded-full {{ $isActive ? 'bg-[#132A6E]' : 'bg-[#3C5CF7]' }}"
                            style="width: {{ $width }}%"
                        ></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($this->appointments->isEmpty())
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-10 text-center shadow-sm">
            @include('partials.patient-empty-record-illustration')
            <flux:text class="mt-4 text-zinc-600">{{ __('doctor.appointments.empty') }}</flux:text>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-zinc-200/90 bg-white shadow-sm">
            <table class="min-w-full table-fixed divide-y divide-zinc-100 text-sm">
                <thead class="bg-zinc-50 text-start text-xs font-semibold uppercase text-zinc-500">
                    <tr>
                        <th class="w-[34%] px-4 py-3 text-start">{{ __('doctor.appointments.patient') }}</th>
                        <th class="w-[22%] px-4 py-3 text-center">{{ __('doctor.appointments.date') }}</th>
                        <th class="w-[20%] px-4 py-3 text-center">{{ __('doctor.appointments.time') }}</th>
                        <th class="w-[24%] px-4 py-3 text-center">{{ __('doctor.appointments.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($this->appointments as $row)
                        @php
                            $statusClasses = match ($row->status) {
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                'in_process' => 'bg-amber-100 text-amber-700',
                                'new' => 'bg-sky-100 text-sky-700',
                                'cancelled' => 'bg-rose-100 text-rose-700',
                                default => 'bg-zinc-100 text-zinc-700',
                            };
                        @endphp
                        <tr class="transition hover:bg-zinc-50/70">
                            <td class="px-4 py-3 font-medium text-zinc-900">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-700">
                                        {{ \Illuminate\Support\Str::of((string) $row->patient_name)->explode(' ')->filter()->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') }}
                                    </span>
                                    <span class="truncate">{{ $row->patient_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums text-zinc-700">{{ $row->appointment_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-center tabular-nums text-zinc-700">{{ \Illuminate\Support\Str::limit((string) $row->start_time, 8, '') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $statusClasses }}">
                                    {{ str_replace('_', ' ', (string) $row->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-1">
            {{ $this->appointments->links() }}
        </div>
    @endif
</div>
