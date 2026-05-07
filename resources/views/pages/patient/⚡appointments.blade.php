<?php

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::patient')] #[Title('Appointments')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'ongoing';

    /**
     * @return array<string, list<string>>
     */
    protected function tabStatuses(): array
    {
        return [
            'ongoing' => ['new', 'in_process'],
            'completed' => ['completed'],
        ];
    }

    public function mount(): void
    {
        if (! array_key_exists($this->tab, $this->tabStatuses())) {
            $this->tab = 'ongoing';
        }
    }

    public function selectTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->tabStatuses())) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
    }

    /**
     * @return Builder<Appointment>
     */
    protected function baseQuery(): Builder
    {
        $userId = Auth::id();
        abort_unless(is_int($userId), 403);

        return Appointment::query()
            ->with('doctor:id,name,name_ar')
            ->where('user_id', $userId);
    }

    public function getAppointmentsProperty(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->whereIn('status', $this->tabStatuses()[$this->tab])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(10);
    }

    /**
     * @return Collection<string, int>
     */
    public function getTabCountsProperty(): Collection
    {
        $counts = $this->baseQuery()
            ->whereIn('status', ['new', 'in_process', 'completed'])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        return collect([
            'ongoing' => (int) ($counts['new'] ?? 0) + (int) ($counts['in_process'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
        ]);
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'new' => __('New'),
            'in_process' => __('In process'),
            'completed' => __('Completed'),
            default => str_replace('_', ' ', $status),
        };
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            'new' => 'bg-sky-100 text-sky-700',
            'in_process' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-emerald-100 text-emerald-700',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }

    public function formattedSessionDate(Appointment $appointment): string
    {
        return $appointment->appointment_date?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '--';
    }

    public function formattedSessionTime(Appointment $appointment): string
    {
        $rawStartTime = (string) $appointment->start_time;
        if ($rawStartTime === '') {
            return '--';
        }

        try {
            return Carbon::createFromFormat('H:i:s', $rawStartTime)->format('h:i A');
        } catch (\Throwable) {
            return $rawStartTime;
        }
    }
}; ?>

<div class="mx-auto w-full max-w-5xl px-4 py-4 pb-20 sm:px-6 sm:py-5 sm:pb-10">
    <header class="flex items-center gap-3">
        <a
            href="{{ route('patient.home') }}"
            wire:navigate
            class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-200/90 bg-white text-[#1565c0] shadow-sm transition hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30"
            aria-label="{{ __('patient.appointments.back_aria') }}"
        >
            <flux:icon name="chevron-left" variant="outline" class="size-6 rtl:rotate-180" />
        </a>
        <h1 class="min-w-0 truncate text-xl font-bold text-[#1565c0] sm:text-2xl">
            {{ __('patient.appointments.title') }}
        </h1>
    </header>

    <div class="mt-2">
        <p class="text-sm text-zinc-500">
            {{ __('patient.appointments.tabs_aria') }}
        </p>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2.5 lg:max-w-xl">
        <button
            type="button"
            wire:click="selectTab('ongoing')"
            class="rounded-xl border p-3 text-start shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30 {{ $tab === 'ongoing' ? 'border-[#1565c0] bg-[#1565c0]/5' : 'border-zinc-200 bg-white hover:border-[#1565c0]/35' }}"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient.appointments.tab_ongoing') }}</p>
            <p class="mt-1 text-2xl font-bold text-[#1565c0]">{{ $this->tabCounts['ongoing'] ?? 0 }}</p>
        </button>
        <button
            type="button"
            wire:click="selectTab('completed')"
            class="rounded-xl border p-3 text-start shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30 {{ $tab === 'completed' ? 'border-[#1565c0] bg-[#1565c0]/5' : 'border-zinc-200 bg-white hover:border-[#1565c0]/35' }}"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient.appointments.tab_completed') }}</p>
            <p class="mt-1 text-2xl font-bold text-[#1565c0]">{{ $this->tabCounts['completed'] ?? 0 }}</p>
        </button>
    </div>

    <div class="mt-3 flex flex-col items-stretch gap-2.5 sm:mt-4 sm:flex-row sm:flex-wrap sm:items-center">
        @php
            $tabActive = 'rounded-lg border border-[#1565c0] bg-[#1565c0] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/40';
            $tabInactive = 'rounded-lg border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#1565c0] shadow-sm transition outline-none hover:border-[#1565c0]/35 focus-visible:ring-2 focus-visible:ring-[#1565c0]/30';
        @endphp

        <div
            class="flex w-full gap-2 sm:w-auto sm:max-w-full sm:flex-wrap"
            role="tablist"
            aria-label="{{ __('patient.appointments.tabs_aria') }}"
        >
            <flux:button
                type="button"
                role="tab"
                wire:click="selectTab('ongoing')"
                :aria-selected="$tab === 'ongoing'"
                class="min-h-11 shrink-0 {{ $tab === 'ongoing' ? $tabActive : $tabInactive }}"
            >
                {{ __('patient.appointments.tab_ongoing') }}
            </flux:button>
            <flux:button
                type="button"
                role="tab"
                wire:click="selectTab('completed')"
                :aria-selected="$tab === 'completed'"
                class="min-h-11 shrink-0 {{ $tab === 'completed' ? $tabActive : $tabInactive }}"
            >
                {{ __('patient.appointments.tab_completed') }}
            </flux:button>
        </div>

        <a
            href="{{ route('patient.schedule.filter') }}"
            wire:navigate
            role="button"
            class="{{ $tabInactive }} inline-flex min-h-11 w-full items-center justify-center text-center no-underline sm:w-auto sm:min-w-[14rem]"
        >
            {{ __('patient.appointments.book_new') }}
        </a>
    </div>

    @if ($this->appointments->isEmpty())
        <section class="mt-10 flex flex-col items-center pb-8 text-center sm:mt-12" role="tabpanel" aria-live="polite">
            @include('partials.patient-empty-record-illustration')
            <p class="mt-8 text-base font-medium text-zinc-400 sm:text-lg">
                {{ __('patient.menu.no_record_found') }}
            </p>
        </section>
    @else
        <section class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2">
            @foreach ($this->appointments as $appointment)
                <article class="h-full rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start gap-3">
                        <div class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-[#1565c0]/10 text-sm font-semibold text-[#1565c0]">
                            {{ \Illuminate\Support\Str::of((string) ($appointment->doctor?->displayName() ?: 'DR'))->explode(' ')->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->implode('') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="truncate text-sm font-semibold text-zinc-900 sm:text-base">
                                    {{ $appointment->doctor?->displayName() ?: __('patient.appointments.title') }}
                                </h2>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->statusBadgeClasses((string) $appointment->status) }}">
                                    {{ $this->statusLabel((string) $appointment->status) }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-zinc-600 sm:grid-cols-2">
                                <div class="inline-flex items-center gap-1.5">
                                    <flux:icon name="calendar-days" variant="mini" class="size-4 text-zinc-400" />
                                    <span>{{ $this->formattedSessionDate($appointment) }}</span>
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    <flux:icon name="clock" variant="mini" class="size-4 text-zinc-400" />
                                    <span>{{ $this->formattedSessionTime($appointment) }}</span>
                                </div>
                            </div>

                            <div class="mt-2 inline-flex items-center gap-1.5 text-xs text-zinc-500">
                                <flux:icon name="user" variant="mini" class="size-4 text-zinc-400" />
                                <span class="truncate">{{ $appointment->patient_name }}</span>
                            </div>
                        </div>
                    </div>

                    @if (filled($appointment->appointment_number))
                        <div class="mt-3 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400">
                            #{{ $appointment->appointment_number }}
                        </div>
                    @endif
                </article>
            @endforeach
        </section>

        @if ($this->appointments->hasPages())
            <div class="mt-4 rounded-xl border border-zinc-200/80 bg-white px-3 py-2 shadow-sm">
                {{ $this->appointments->links() }}
            </div>
        @endif
    @endif
</div>
