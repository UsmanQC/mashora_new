<?php

use App\Livewire\Concerns\CompletesDoctorAppointment;
use App\Models\Appointment;
use App\Support\DoctorAppointmentWorkflow;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Medical history')] class extends Component
{
    use CompletesDoctorAppointment;

    public Appointment $appointment;

    public function mount(): void
    {
        app(DoctorAppointmentWorkflow::class)->markHistoryReviewed($this->appointment);
    }

    /**
     * @return EloquentCollection<int, Appointment>
     */
    #[Computed]
    public function medicalHistories(): EloquentCollection
    {
        if ($this->appointment->user_id === null) {
            return new EloquentCollection;
        }

        return Appointment::query()
            ->with(['diagnosis', 'medications'])
            ->withCount('medications')
            ->where('user_id', $this->appointment->user_id)
            ->where('id', '!=', $this->appointment->id)
            ->where('status', 'completed')
            ->orderByDesc('scheduled_at')
            ->get();
    }
}; ?>

<div class="space-y-8">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'medical_history'])

    <div class="flex items-center gap-3">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[#10B981]/10 text-[#047857]">
            <flux:icon name="clipboard-document-list" variant="outline" class="size-5" />
        </div>
        <div class="min-w-0">
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.medical_history.title') }}</flux:heading>
            <flux:text class="mt-0.5 text-zinc-600">{{ __('doctor.medical_history.subtitle') }}</flux:text>
        </div>
    </div>

    @if ($this->medicalHistories->isEmpty())
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-10 text-center shadow-sm">
            @include('partials.patient-empty-record-illustration')
            <flux:text class="mt-4 text-zinc-600">{{ __('doctor.medical_history.empty') }}</flux:text>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($this->medicalHistories as $entry)
                <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-1 border-b border-zinc-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
                        <flux:heading size="md" class="font-semibold text-zinc-900">
                            {{ __('doctor.workspace.appointment_number', ['number' => $entry->appointment_number]) }}
                        </flux:heading>
                        <p class="text-sm tabular-nums text-zinc-500">
                            {{ optional($entry->scheduled_at)->format('d/m/Y h:i A') }}
                        </p>
                    </div>

                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="font-semibold text-zinc-900">{{ __('doctor.medical_history.card_diagnosis') }}</dt>
                            <dd class="mt-1 text-zinc-700">{{ optional($entry->diagnosis)->diagnosis_name ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900">{{ __('doctor.medical_history.card_history') }}</dt>
                            <dd class="mt-1 whitespace-pre-line text-zinc-700">{{ optional($entry->diagnosis)->medical_history ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900">{{ __('doctor.medical_history.card_notes') }}</dt>
                            <dd class="mt-1 whitespace-pre-line text-zinc-700">{{ optional($entry->diagnosis)->treatment_plan ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900">{{ __('doctor.medical_history.card_prescriptions') }}</dt>
                            <dd class="mt-1">
                                @if ($entry->medications_count > 0)
                                    <div class="overflow-x-auto rounded-xl border border-zinc-200">
                                        <table class="min-w-full divide-y divide-zinc-200 text-sm">
                                            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                                                <tr>
                                                    <th class="px-3 py-2 text-start font-semibold">{{ __('doctor.medical_history.col_medicine') }}</th>
                                                    <th class="px-3 py-2 text-start font-semibold">{{ __('doctor.medical_history.col_usage') }}</th>
                                                    <th class="px-3 py-2 text-start font-semibold">{{ __('doctor.medical_history.col_frequency') }}</th>
                                                    <th class="px-3 py-2 text-start font-semibold">{{ __('doctor.medical_history.col_period') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-100 bg-white">
                                                @foreach ($entry->medications as $med)
                                                    <tr>
                                                        <td class="px-3 py-2">
                                                            <p class="font-semibold text-zinc-900">{{ $med->name }}</p>
                                                            @if ($med->dosage)
                                                                <p class="text-xs text-zinc-500">{{ $med->dosage }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-zinc-700">{{ $med->usage ?: '—' }}</td>
                                                        <td class="px-3 py-2 text-zinc-700">{{ $med->frequency ?: '—' }}</td>
                                                        <td class="px-3 py-2 tabular-nums text-zinc-700">
                                                            {{ trim(($med->duration ?? '').' '.($med->duration_measurement ?? '')) ?: '—' }}
                                                        </td>
                                                    </tr>
                                                    @if (filled($med->instructions))
                                                        <tr>
                                                            <td colspan="4" class="bg-zinc-50/60 px-3 py-2 text-xs italic text-zinc-600">
                                                                {{ $med->instructions }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-zinc-500">{{ __('doctor.medical_history.no_prescription') }}</p>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @endforeach
        </div>
    @endif

    @include('partials.doctor-complete-appointment-modals')
</div>
