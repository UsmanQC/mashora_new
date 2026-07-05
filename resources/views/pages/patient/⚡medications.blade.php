<?php

use App\Models\Appointment;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Medications')] class extends Component
{
    protected function patient(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getPrescriptionAppointmentsProperty(): Collection
    {
        return Appointment::query()
            ->where('user_id', $this->patient()->id)
            ->whereHas('medications')
            ->with([
                'medications',
                'doctor:id,name,name_ar',
                'diagnosis:id,appointment_id,diagnosis_name',
            ])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }

    public function formattedSessionDate(Appointment $appointment): string
    {
        $startsAt = $appointment->sessionStartsAt();

        if ($startsAt === null) {
            return '—';
        }

        return $startsAt->locale(app()->getLocale())->translatedFormat('d M Y');
    }

    public function doctorName(Appointment $appointment): string
    {
        return $appointment->doctor?->displayName() ?: __('patient.appointments.specialist_label');
    }

    public function durationLabel(Medication $medication): string
    {
        $duration = trim((string) ($medication->duration ?? ''));

        if ($duration === '') {
            return '—';
        }

        $unit = match ((string) $medication->duration_measurement) {
            'days' => __('patient.medications_page.unit_days'),
            'week' => __('patient.medications_page.unit_week'),
            'month' => __('patient.medications_page.unit_month'),
            default => (string) $medication->duration_measurement,
        };

        return trim($duration.' '.$unit);
    }
}; ?>

<div class="patient-luxury-medications bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-medications">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.menu.medications'),
            'subtitle' => __('patient.menu.medications_sub'),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'backUrl' => route('patient.menu'),
            'backLabel' => __('patient.nav.menu'),
            'testId' => 'patient-medications-header',
        ])
    </div>

    <div class="mx-auto max-w-3xl space-y-5 px-6 pt-5 sm:space-y-6 sm:px-4 sm:py-8">
        <div class="hidden items-start justify-between gap-4 sm:flex">
            <div>
                <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('patient.menu.medications') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600">{{ __('patient.menu.medications_sub') }}</flux:text>
            </div>
            <flux:button :href="route('patient.menu')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('patient.empty_state.menu_crumb') }}
            </flux:button>
        </div>

        @if ($this->prescriptionAppointments->isEmpty())
            <section class="flex flex-col items-center rounded-3xl border border-slate-100/80 bg-white px-6 py-14 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-2 flex size-16 items-center justify-center rounded-full bg-emerald-50 text-[#10B981]">
                    <flux:icon name="clipboard-document" variant="outline" class="size-8" />
                </div>
                <flux:heading size="lg" class="font-semibold text-slate-900">{{ __('patient.medications_page.empty_title') }}</flux:heading>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-500">{{ __('patient.medications_page.empty_hint') }}</p>
            </section>
        @else
            <div class="space-y-4">
                @foreach ($this->prescriptionAppointments as $appointment)
                    <article
                        class="overflow-hidden rounded-3xl border border-slate-100/80 bg-white shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm"
                        wire:key="patient-prescription-{{ $appointment->id }}"
                        data-test="patient-prescription-card"
                    >
                        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#059669]">{{ __('patient.medications_page.session_label') }}</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $this->doctorName($appointment) }}</p>
                                    @if (filled($appointment->diagnosis?->diagnosis_name))
                                        <p class="mt-1 text-xs text-slate-500">{{ $appointment->diagnosis->diagnosis_name }}</p>
                                    @endif
                                </div>
                                <p class="shrink-0 text-xs font-semibold tabular-nums text-slate-500">{{ $this->formattedSessionDate($appointment) }}</p>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach ($appointment->medications as $medication)
                                <div class="px-5 py-4" wire:key="patient-medication-{{ $medication->id }}">
                                    <p class="text-sm font-bold text-slate-900">{{ $medication->name }}</p>
                                    @if (filled($medication->dosage))
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ __('patient.medications_page.dosage') }}: {{ $medication->dosage }}
                                        </p>
                                    @endif

                                    <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                                        @if (filled($medication->usage))
                                            <div>
                                                <dt class="font-semibold text-slate-500">{{ __('patient.medications_page.usage') }}</dt>
                                                <dd class="mt-0.5 text-slate-800">{{ $medication->usage }}</dd>
                                            </div>
                                        @endif
                                        @if (filled($medication->frequency))
                                            <div>
                                                <dt class="font-semibold text-slate-500">{{ __('patient.medications_page.frequency') }}</dt>
                                                <dd class="mt-0.5 text-slate-800">{{ $medication->frequency }}</dd>
                                            </div>
                                        @endif
                                        @if ($this->durationLabel($medication) !== '—')
                                            <div>
                                                <dt class="font-semibold text-slate-500">{{ __('patient.medications_page.duration') }}</dt>
                                                <dd class="mt-0.5 text-slate-800">{{ $this->durationLabel($medication) }}</dd>
                                            </div>
                                        @endif
                                    </dl>

                                    @if (filled($medication->instructions))
                                        <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs italic leading-relaxed text-slate-600">
                                            {{ $medication->instructions }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
