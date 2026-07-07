<?php

use App\Livewire\Concerns\CompletesDoctorAppointment;
use App\Models\Appointment;
use App\Models\Medication;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Prescription')] class extends Component
{
    use CompletesDoctorAppointment;

    public Appointment $appointment;

    public bool $prescriptionNotNeeded = false;

    public bool $completeModalFromPrescriptionToggle = false;

    public bool $showMedicationModal = false;

    public ?int $editingMedicationId = null;

    public ?string $name = null;

    public ?string $dosage = null;

    public ?string $usage = null;

    public ?string $frequency = null;

    public ?string $duration = null;

    public ?string $duration_measurement = null;

    public ?string $instructions = null;

    public function mount(): void
    {
        $this->prescriptionNotNeeded = (bool) $this->appointment->prescription_not_needed;
    }

    /**
     * @return EloquentCollection<int, Medication>
     */
    #[Computed]
    public function medications(): EloquentCollection
    {
        return $this->appointment->medications()->orderBy('id')->get();
    }

    public function updatedPrescriptionNotNeeded(bool $value): void
    {
        if ($value) {
            $this->appointment->forceFill(['prescription_not_needed' => true])->save();
            $this->completeModalFromPrescriptionToggle = true;
            $this->requestCompleteAppointment();

            return;
        }

        $this->appointment->forceFill(['prescription_not_needed' => false])->save();

        Flux::toast(
            variant: 'success',
            text: __('doctor.prescription_form.prescription_toggle_off'),
        );
    }

    public function dismissCompleteAppointmentModal(): void
    {
        $this->revertPrescriptionToggleIfModalCancelled();

        $this->showCompleteAppointmentModal = false;
        $this->appointmentPendingCompleteId = null;
    }

    public function updatedShowCompleteAppointmentModal(bool $value): void
    {
        if (! $value) {
            $this->revertPrescriptionToggleIfModalCancelled();
            $this->appointmentPendingCompleteId = null;
        }
    }

    public function confirmCompleteAppointment(): void
    {
        $this->completeModalFromPrescriptionToggle = false;

        $id = $this->appointmentPendingCompleteId;
        $this->showCompleteAppointmentModal = false;
        $this->appointmentPendingCompleteId = null;

        if ($id === null) {
            return;
        }

        $doctor = $this->doctorForAppointmentCompletion();
        if ($doctor === null) {
            abort(403);
        }

        $appointment = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereKey($id)
            ->first();

        if ($appointment === null) {
            return;
        }

        $result = app(\App\Services\AppointmentCompletionService::class)->attemptCompletion($appointment);

        match ($result) {
            \App\Services\AppointmentCompletionService::MISSING_DIAGNOSIS => $this->showDiagnosisRequiredModal = true,
            \App\Services\AppointmentCompletionService::MISSING_PRESCRIPTION => $this->showPrescriptionRequiredModal = true,
            \App\Services\AppointmentCompletionService::COMPLETED => $this->redirectAfterAppointmentCompletion($appointment->fresh()),
            default => null,
        };
    }

    private function revertPrescriptionToggleIfModalCancelled(): void
    {
        if (! $this->completeModalFromPrescriptionToggle) {
            return;
        }

        $this->prescriptionNotNeeded = false;
        $this->appointment->forceFill(['prescription_not_needed' => false])->save();
        $this->completeModalFromPrescriptionToggle = false;
    }

    public function openCreateMedication(): void
    {
        $this->resetMedicationForm();
        $this->duration_measurement = 'days';
        $this->showMedicationModal = true;
    }

    public function openEditMedication(int $medicationId): void
    {
        $medication = $this->appointment->medications()->whereKey($medicationId)->first();

        if ($medication === null) {
            return;
        }

        $this->editingMedicationId = $medication->id;
        $this->name = $medication->name;
        $this->dosage = $medication->dosage;
        $this->usage = $medication->usage;
        $this->frequency = $medication->frequency;
        $this->duration = $medication->duration;
        $this->duration_measurement = $medication->duration_measurement;
        $this->instructions = $medication->instructions;

        $this->showMedicationModal = true;
    }

    public function dismissMedicationModal(): void
    {
        $this->showMedicationModal = false;
        $this->resetMedicationForm();
    }

    public function updatedShowMedicationModal(bool $value): void
    {
        if (! $value) {
            $this->resetMedicationForm();
        }
    }

    public function saveMedication(): void
    {
        if ($this->duration_measurement === '') {
            $this->duration_measurement = null;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:250'],
            'dosage' => ['required', 'string', 'max:100'],
            'usage' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', 'max:250'],
            'duration' => ['required', 'string', 'max:250'],
            'duration_measurement' => ['required', Rule::in(['days', 'week', 'month'])],
            'instructions' => ['nullable', 'string', 'max:2500'],
        ]);

        if ($this->editingMedicationId !== null) {
            $medication = $this->appointment->medications()->whereKey($this->editingMedicationId)->first();

            if ($medication !== null) {
                $medication->update($validated);
                Flux::toast(variant: 'success', text: __('doctor.prescription_form.medication_updated'));
            }
        } else {
            $this->appointment->medications()->create($validated);
            Flux::toast(variant: 'success', text: __('doctor.prescription_form.medication_added'));
        }

        if ($this->prescriptionNotNeeded) {
            $this->prescriptionNotNeeded = false;
            $this->appointment->forceFill(['prescription_not_needed' => false])->save();
        }

        $this->showMedicationModal = false;
        $this->resetMedicationForm();
        unset($this->medications);
    }

    public function deleteMedication(int $medicationId): void
    {
        $medication = $this->appointment->medications()->whereKey($medicationId)->first();

        if ($medication === null) {
            return;
        }

        $medication->delete();

        Flux::toast(variant: 'success', text: __('doctor.prescription_form.medication_removed'));

        unset($this->medications);
    }

    private function resetMedicationForm(): void
    {
        $this->editingMedicationId = null;
        $this->name = null;
        $this->dosage = null;
        $this->usage = null;
        $this->frequency = null;
        $this->duration = null;
        $this->duration_measurement = null;
        $this->instructions = null;
        $this->resetErrorBag();
    }
}; ?>

<div class="space-y-8">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'prescription'])

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.prescription_form.title') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('doctor.prescription_form.subtitle') }}</flux:text>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @if ($this->medications->isNotEmpty())
                <flux:button
                    :href="route('doctor.appointments.prescription.pdf', $appointment)"
                    variant="outline"
                    icon="arrow-down-tray"
                    class="w-full !border-emerald-200 !text-emerald-800 hover:!bg-emerald-50 sm:w-auto"
                    target="_blank"
                    rel="noopener"
                >
                    {{ __('doctor.prescription_form.download_pdf') }}
                </flux:button>
            @endif
            <flux:button
                type="button"
                variant="primary"
                icon="plus"
                class="w-full !bg-[#10B981] hover:!brightness-95 sm:w-auto"
                wire:click="openCreateMedication"
            >
                {{ __('doctor.prescription_form.add_medication') }}
            </flux:button>
        </div>
    </div>

    @if ($this->medications->isNotEmpty())
        <div class="rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-teal-50/60 px-4 py-3.5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-950">{{ __('doctor.prescription_form.download_pdf') }}</p>
                    <p class="mt-0.5 text-xs text-emerald-900/80">{{ __('doctor.prescription_form.download_pdf_hint') }}</p>
                </div>
                <flux:button
                    :href="route('doctor.appointments.prescription.pdf', $appointment)"
                    variant="primary"
                    icon="document-arrow-down"
                    class="w-full shrink-0 !bg-[#047857] hover:!brightness-95 sm:w-auto"
                    target="_blank"
                    rel="noopener"
                >
                    {{ __('doctor.prescription_form.download_pdf') }}
                </flux:button>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="md" class="font-semibold text-zinc-900">
                    {{ __('doctor.prescription_form.no_prescription_needed') }}
                </flux:heading>
                @if ($prescriptionNotNeeded)
                    <flux:text class="mt-1 text-sm text-zinc-600">
                        {{ __('doctor.prescription_form.no_prescription_locked') }}
                    </flux:text>
                @endif
            </div>
            <div class="shrink-0 [--color-accent:#10B981] [--color-accent-foreground:#ffffff]">
                <flux:switch wire:model.live="prescriptionNotNeeded" />
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white shadow-sm">
        @if ($this->medications->isEmpty())
            <div class="px-4 py-10 text-center">
                <flux:text class="text-zinc-600">{{ __('doctor.prescription_form.no_medications') }}</flux:text>
            </div>
        @else
            <div class="space-y-3 p-4 sm:hidden">
                @foreach ($this->medications as $med)
                    <div wire:key="med-mobile-{{ $med->id }}" class="rounded-xl border border-zinc-200/80 p-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold text-zinc-900">{{ $med->name }}</p>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button
                                    type="button"
                                    wire:click="openEditMedication({{ $med->id }})"
                                    class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-600"
                                    aria-label="{{ __('doctor.prescription_form.edit_medication') }}"
                                >
                                    <flux:icon name="pencil-square" variant="mini" class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="deleteMedication({{ $med->id }})"
                                    wire:confirm="{{ __('doctor.complete_modal.body') }}"
                                    class="flex size-8 items-center justify-center rounded-full bg-rose-50 text-rose-600"
                                    aria-label="{{ __('doctor.prescription_form.remove') }}"
                                >
                                    <flux:icon name="trash" variant="mini" class="size-4" />
                                </button>
                            </div>
                        </div>

                        <dl class="mt-2.5 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-zinc-400">{{ __('doctor.prescription_form.col_dosage') }}</dt>
                                <dd class="mt-0.5 text-zinc-700">{{ $med->dosage ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-zinc-400">{{ __('doctor.prescription_form.col_usage') }}</dt>
                                <dd class="mt-0.5 text-zinc-700">{{ $med->usage ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-zinc-400">{{ __('doctor.prescription_form.col_frequency') }}</dt>
                                <dd class="mt-0.5 text-zinc-700">{{ $med->frequency ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-zinc-400">{{ __('doctor.prescription_form.col_duration') }}</dt>
                                <dd class="mt-0.5 tabular-nums text-zinc-700">
                                    {{ trim(($med->duration ?? '').' '.($med->duration_measurement ?? '')) ?: '—' }}
                                </dd>
                            </div>
                        </dl>

                        @if (filled($med->instructions))
                            <p class="mt-2.5 border-t border-zinc-100 pt-2.5 text-xs italic text-zinc-600">
                                {{ $med->instructions }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('doctor.prescription_form.col_medicine') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('doctor.prescription_form.col_dosage') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('doctor.prescription_form.col_usage') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('doctor.prescription_form.col_frequency') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('doctor.prescription_form.col_duration') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('doctor.prescription_form.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @foreach ($this->medications as $med)
                            <tr wire:key="med-{{ $med->id }}">
                                <td class="px-4 py-3 font-semibold text-zinc-900">{{ $med->name }}</td>
                                <td class="px-4 py-3 text-zinc-700">{{ $med->dosage ?: '—' }}</td>
                                <td class="px-4 py-3 text-zinc-700">{{ $med->usage ?: '—' }}</td>
                                <td class="px-4 py-3 text-zinc-700">{{ $med->frequency ?: '—' }}</td>
                                <td class="px-4 py-3 tabular-nums text-zinc-700">
                                    {{ trim(($med->duration ?? '').' '.($med->duration_measurement ?? '')) ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button
                                            type="button"
                                            size="xs"
                                            variant="outline"
                                            icon="pencil-square"
                                            wire:click="openEditMedication({{ $med->id }})"
                                        >
                                            {{ __('doctor.prescription_form.edit_medication') }}
                                        </flux:button>
                                        <flux:button
                                            type="button"
                                            size="xs"
                                            variant="outline"
                                            icon="trash"
                                            class="!border-rose-200 !text-rose-600 hover:!bg-rose-50"
                                            wire:click="deleteMedication({{ $med->id }})"
                                            wire:confirm="{{ __('doctor.complete_modal.body') }}"
                                        >
                                            {{ __('doctor.prescription_form.remove') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                            @if (filled($med->instructions))
                                <tr class="bg-zinc-50/60">
                                    <td colspan="6" class="px-4 py-2 text-xs italic text-zinc-600">
                                        {{ $med->instructions }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <flux:modal wire:model.self="showMedicationModal" class="max-w-2xl rounded-2xl shadow-xl" :closable="true">
        <form wire:submit="saveMedication" class="space-y-6 p-1">
            <div>
                <flux:heading size="lg" class="font-semibold text-zinc-900">
                    {{ $editingMedicationId
                        ? __('doctor.prescription_form.edit_medication')
                        : __('doctor.prescription_form.add_medication') }}
                </flux:heading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('doctor.prescription_form.medicine_name')" required />
                <flux:input wire:model="dosage" :label="__('doctor.prescription_form.dosage')" required />
                <flux:input wire:model="usage" :label="__('doctor.prescription_form.usage')" required />
                <flux:input wire:model="frequency" :label="__('doctor.prescription_form.frequency')" required />
                <flux:field>
                    <flux:label>{{ __('doctor.prescription_form.duration_measurement') }}</flux:label>
                    <flux:select wire:model.live="duration_measurement" :placeholder="__('doctor.prescription_form.choose')">
                        <flux:select.option value="days">{{ __('doctor.prescription_form.unit_days') }}</flux:select.option>
                        <flux:select.option value="week">{{ __('doctor.prescription_form.unit_week') }}</flux:select.option>
                        <flux:select.option value="month">{{ __('doctor.prescription_form.unit_month') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="duration_measurement" />
                </flux:field>
                <flux:input wire:model="duration" :label="__('doctor.prescription_form.duration')" type="number" min="1" required />
            </div>

            <flux:textarea
                wire:model="instructions"
                :label="__('doctor.prescription_form.instructions')"
                :placeholder="__('doctor.prescription_form.instructions_placeholder')"
                rows="3"
            />

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <flux:button type="button" variant="ghost" wire:click="dismissMedicationModal">
                    {{ __('doctor.prescription_form.cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="!bg-[#10B981] hover:!brightness-95">
                    {{ $editingMedicationId
                        ? __('doctor.prescription_form.update')
                        : __('doctor.prescription_form.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    @include('partials.doctor-complete-appointment-modals')
</div>
