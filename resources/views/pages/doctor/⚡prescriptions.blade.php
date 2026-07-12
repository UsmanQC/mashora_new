<?php

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Prescriptions')] class extends Component
{
    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getPrescriptionsProperty(): Collection
    {
        return $this->doctor()->appointments()
            ->whereIn('status', ['completed', 'in_process'])
            ->withCount('medications')
            ->latest('appointment_date')
            ->latest('start_time')
            ->limit(50)
            ->get();
    }

    public function statusLabel(Appointment $appointment): string
    {
        if ($appointment->prescription_not_needed) {
            return __('doctor.prescriptions_list.status_not_needed');
        }

        return $appointment->medications_count > 0
            ? __('doctor.prescriptions_list.status_prescribed')
            : __('doctor.prescriptions_list.status_pending');
    }

    public function statusClasses(Appointment $appointment): string
    {
        if ($appointment->prescription_not_needed) {
            return 'bg-zinc-100 text-zinc-600';
        }

        return $appointment->medications_count > 0
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-amber-100 text-amber-900';
    }
}; ?>

<div class="relative w-full">
    @include('partials.doctor-luxury-prescriptions-mobile')

    <div class="hidden space-y-6 lg:block">
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.prescriptions_list.title') }}</flux:heading>
            <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
        </div>

        <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
            <div class="space-y-3">
                @forelse ($this->prescriptions as $appointment)
                    <a
                        href="{{ route('doctor.appointments.prescription', $appointment) }}"
                        wire:navigate
                        class="block rounded-xl border border-zinc-200 p-4 transition hover:border-[#10B981]/35 hover:shadow-sm"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <flux:text class="font-semibold text-zinc-900">{{ $appointment->patient_name }}</flux:text>
                                @if ($appointment->appointment_number)
                                    <flux:text class="mt-0.5 text-xs uppercase tracking-wide text-zinc-500">{{ $appointment->appointment_number }}</flux:text>
                                @endif
                                <flux:text class="mt-1 text-xs text-zinc-500">
                                    {{ $appointment->appointment_date?->format('d/m/Y') }}
                                    @if ($appointment->formattedSessionStart() !== '')
                                        · {{ $appointment->formattedSessionStart() }}
                                    @endif
                                </flux:text>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->statusClasses($appointment) }}">
                                {{ $this->statusLabel($appointment) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 text-center">
                        <flux:text class="text-sm text-zinc-500">{{ __('doctor.prescriptions_list.empty') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
