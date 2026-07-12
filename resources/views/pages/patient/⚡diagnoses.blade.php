<?php

use App\Models\Appointment;
use App\Models\User;
use App\Services\DiagnosisPdfService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Diagnosis reports')] class extends Component
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
    public function getDiagnosisAppointmentsProperty(): Collection
    {
        return Appointment::query()
            ->where('user_id', $this->patient()->id)
            ->whereHas('diagnosis')
            ->with([
                'doctor:id,name,name_ar,degree_id',
                'doctor.degree:id,title,title_ar',
                'diagnosis',
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

    public function degreeLabel(Appointment $appointment): ?string
    {
        $degree = $appointment->doctor?->degree;

        if ($degree === null) {
            return null;
        }

        if (app()->getLocale() === 'ar' && filled($degree->title_ar)) {
            return (string) $degree->title_ar;
        }

        $label = filled($degree->title) ? (string) $degree->title : (string) ($degree->title_ar ?? '');

        return $label !== '' ? $label : null;
    }

    public function maritalStatusLabel(?string $status): string
    {
        return app(DiagnosisPdfService::class)->maritalStatusLabel($status);
    }
}; ?>

<div class="patient-luxury-diagnoses bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-diagnoses">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.menu.diagnoses'),
            'subtitle' => __('patient.menu.diagnoses_sub'),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'backUrl' => route('patient.menu'),
            'backLabel' => __('patient.nav.menu'),
            'testId' => 'patient-diagnoses-header',
        ])
    </div>

    <div class="mx-auto max-w-3xl space-y-5 px-6 pt-5 sm:space-y-6 sm:px-4 sm:py-8">
        <div class="hidden items-start justify-between gap-4 sm:flex">
            <div>
                <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('patient.menu.diagnoses') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600">{{ __('patient.menu.diagnoses_sub') }}</flux:text>
            </div>
            <flux:button :href="route('patient.menu')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('patient.empty_state.menu_crumb') }}
            </flux:button>
        </div>

        @if ($this->diagnosisAppointments->isEmpty())
            <section class="flex flex-col items-center rounded-3xl border border-slate-100/80 bg-white px-6 py-14 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-2 flex size-16 items-center justify-center rounded-full bg-emerald-50 text-[#10B981]">
                    <flux:icon name="document-text" variant="outline" class="size-8" />
                </div>
                <flux:heading size="lg" class="font-semibold text-slate-900">{{ __('patient.diagnoses_page.empty_title') }}</flux:heading>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-500">{{ __('patient.diagnoses_page.empty_hint') }}</p>
            </section>
        @else
            <div class="space-y-4">
                @foreach ($this->diagnosisAppointments as $appointment)
                    @php
                        $diagnosis = $appointment->diagnosis;
                        $degreeLabel = $this->degreeLabel($appointment);
                        $maritalLabel = $this->maritalStatusLabel($diagnosis?->marital_status);
                    @endphp
                    <article
                        class="overflow-hidden rounded-3xl border border-slate-100/80 bg-white shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm"
                        wire:key="patient-diagnosis-{{ $appointment->id }}"
                        data-test="patient-diagnosis-card"
                    >
                        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                            <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:items-start sm:justify-between sm:text-start">
                                <div class="min-w-0 w-full sm:w-auto">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#059669]">{{ __('patient.diagnoses_page.session_label') }}</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $this->doctorName($appointment) }}</p>
                                    @if (filled($degreeLabel))
                                        <p class="mt-1 text-xs text-slate-500">{{ $degreeLabel }}</p>
                                    @endif
                                    <p class="mt-2 text-xs font-semibold tabular-nums text-slate-500 sm:hidden">{{ $this->formattedSessionDate($appointment) }}</p>
                                </div>
                                <div class="flex w-full flex-col items-center gap-2 sm:w-auto sm:items-end">
                                    <p class="hidden text-xs font-semibold tabular-nums text-slate-500 sm:block">{{ $this->formattedSessionDate($appointment) }}</p>
                                    <div class="inline-flex overflow-hidden rounded-full border border-emerald-200/90 bg-white shadow-sm ring-1 ring-emerald-100/80">
                                        <flux:button
                                            :href="route('patient.diagnoses.preview', $appointment)"
                                            size="sm"
                                            variant="ghost"
                                            icon="eye"
                                            class="!rounded-none !border-0 !border-e !border-emerald-100 !bg-white !px-4 !py-2 !text-sm !font-semibold !text-[#047857] hover:!bg-emerald-50"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            {{ __('patient.diagnoses_page.preview_pdf') }}
                                        </flux:button>
                                        <flux:button
                                            :href="route('patient.diagnoses.pdf', $appointment)"
                                            size="sm"
                                            variant="ghost"
                                            icon="arrow-down-tray"
                                            class="!rounded-none !border-0 !bg-[#047857] !px-4 !py-2 !text-sm !font-semibold !text-white hover:!bg-[#065f46]"
                                        >
                                            {{ __('patient.diagnoses_page.download_pdf') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <div class="px-5 py-4">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('patient.diagnoses_page.diagnosis_name') }}</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $diagnosis?->diagnosis_name ?: '—' }}</p>

                                @if ($maritalLabel !== '—')
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ __('patient.diagnoses_page.marital_status') }}: {{ $maritalLabel }}
                                    </p>
                                @endif
                            </div>

                            @if (filled($diagnosis?->medical_history))
                                <div class="px-5 py-4">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('patient.diagnoses_page.medical_history') }}</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $diagnosis->medical_history }}</p>
                                </div>
                            @endif

                            @if (filled($diagnosis?->treatment_plan))
                                <div class="px-5 py-4">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('patient.diagnoses_page.treatment_plan') }}</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $diagnosis->treatment_plan }}</p>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
