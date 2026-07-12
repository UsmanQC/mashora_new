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

        return $startsAt->locale(app()->getLocale())->translatedFormat('d M Y · g:i a');
    }

    public function doctorName(Appointment $appointment): string
    {
        return $appointment->doctor?->displayName() ?: __('patient.appointments.specialist_label');
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
                    @endphp
                    <article
                        class="overflow-hidden rounded-3xl border border-slate-100/80 bg-white shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm"
                        wire:key="patient-diagnosis-{{ $appointment->id }}"
                        data-test="patient-diagnosis-card"
                    >
                        <div class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 px-5 py-5">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#10B981] via-[#059669] to-[#34d399]"></div>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#059669]">{{ __('patient.diagnoses_page.session_label') }}</p>
                                    <p class="mt-1 text-base font-bold text-slate-900">{{ $this->doctorName($appointment) }}</p>
                                    @if (filled($appointment->doctor?->degree?->title) || filled($appointment->doctor?->degree?->title_ar))
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ app()->getLocale() === 'ar' && filled($appointment->doctor->degree->title_ar)
                                                ? $appointment->doctor->degree->title_ar
                                                : ($appointment->doctor->degree->title ?: $appointment->doctor->degree->title_ar) }}
                                        </p>
                                    @endif
                                    <p class="mt-2 text-xs font-semibold tabular-nums text-slate-500">{{ $this->formattedSessionDate($appointment) }}</p>
                                </div>
                                <div class="inline-flex w-full overflow-hidden rounded-full border border-emerald-200/90 bg-white shadow-sm ring-1 ring-emerald-100/80 sm:w-auto">
                                    <flux:button
                                        :href="route('patient.diagnoses.preview', $appointment)"
                                        size="sm"
                                        variant="ghost"
                                        icon="eye"
                                        class="!flex-1 !rounded-none !border-0 !border-e !border-emerald-100 !bg-white !px-4 !py-2 !text-sm !font-semibold !text-[#047857] hover:!bg-emerald-50 sm:!flex-none"
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
                                        class="!flex-1 !rounded-none !border-0 !bg-[#047857] !px-4 !py-2 !text-sm !font-semibold !text-white hover:!bg-[#065f46] sm:!flex-none"
                                    >
                                        {{ __('patient.diagnoses_page.download_pdf') }}
                                    </flux:button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('patient.diagnoses_page.diagnosis_name') }}</p>
                                <p class="mt-1 text-lg font-extrabold tracking-tight text-slate-900">{{ $diagnosis?->diagnosis_name ?: '—' }}</p>
                            </div>

                            @if ($this->maritalStatusLabel($diagnosis?->marital_status) !== '—')
                                <div class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200/80">
                                    <flux:icon name="user" class="size-3.5 text-slate-400" />
                                    {{ __('patient.diagnoses_page.marital_status') }}:
                                    {{ $this->maritalStatusLabel($diagnosis?->marital_status) }}
                                </div>
                            @endif

                            @if (filled($diagnosis?->medical_history))
                                <section class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#059669]">{{ __('patient.diagnoses_page.medical_history') }}</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $diagnosis->medical_history }}</p>
                                </section>
                            @endif

                            @if (filled($diagnosis?->treatment_plan))
                                <section class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#047857]">{{ __('patient.diagnoses_page.treatment_plan') }}</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-800">{{ $diagnosis->treatment_plan }}</p>
                                </section>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
