<?php

use App\Livewire\Concerns\CompletesDoctorAppointment;
use App\Models\Appointment;
use App\Models\Diagnosis;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Diagnosis')] class extends Component
{
    use CompletesDoctorAppointment;

    public Appointment $appointment;

    public ?string $marital_status = 'unmarried';

    public ?string $diagnosis_name = null;

    public ?string $medical_history = null;

    public ?string $doctor_notes = null;

    public ?string $treatment_plan = null;

    public function mount(): void
    {
        $diagnosis = $this->appointment->diagnosis;

        if ($diagnosis instanceof Diagnosis) {
            $this->marital_status = $diagnosis->marital_status ?: 'unmarried';
            $this->diagnosis_name = $diagnosis->diagnosis_name;
            $this->medical_history = $diagnosis->medical_history;
            $this->doctor_notes = $diagnosis->doctor_notes;
            $this->treatment_plan = $diagnosis->treatment_plan;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'marital_status' => ['required', Rule::in(['married', 'unmarried'])],
            'diagnosis_name' => ['required', 'string', 'max:255'],
            'medical_history' => ['nullable', 'string', 'max:2500'],
            'doctor_notes' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
        ]);

        Diagnosis::updateOrCreate(
            ['appointment_id' => $this->appointment->id],
            $validated,
        );

        Flux::toast(variant: 'success', text: __('doctor.diagnosis_form.saved'));

        $this->redirect(route('doctor.appointments.prescription', $this->appointment), navigate: true);
    }
}; ?>

<div class="space-y-6">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'diagnosis'])

    <div class="flex items-start gap-3">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-[#10B981] ring-1 ring-emerald-100">
            <flux:icon name="document-text" variant="mini" class="size-5" />
        </span>
        <div class="min-w-0">
            <flux:heading size="xl" class="font-semibold tracking-tight text-zinc-900">{{ __('doctor.diagnosis_form.title') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">{{ __('doctor.diagnosis_form.subtitle') }}</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-sm ring-1 ring-zinc-900/[0.03]">
            <div class="border-b border-zinc-100 bg-gradient-to-b from-zinc-50/80 to-white px-5 py-5 sm:px-6">
                <flux:field>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <flux:label class="!mb-0 text-sm font-semibold text-zinc-900">{{ __('doctor.diagnosis_form.is_patient_married') }}</flux:label>
                        <div class="doctor-emerald-pill-radios mx-auto w-full max-w-xs sm:mx-0 sm:shrink-0 sm:justify-self-end">
                            <flux:radio.group variant="pills" wire:model.live="marital_status" class="w-full">
                                <flux:radio value="married" :label="__('doctor.diagnosis_form.yes')" />
                                <flux:radio value="unmarried" :label="__('doctor.diagnosis_form.no')" />
                            </flux:radio.group>
                        </div>
                    </div>
                    <flux:error name="marital_status" class="mt-2 text-center sm:text-end" />
                </flux:field>
            </div>

            <div class="doctor-clinical-form space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                <flux:input
                    wire:model="diagnosis_name"
                    :label="__('doctor.diagnosis_form.diagnosis_name')"
                    required
                />

                <flux:textarea
                    wire:model="medical_history"
                    :label="__('doctor.diagnosis_form.medical_history')"
                    :placeholder="__('doctor.diagnosis_form.medical_history_placeholder')"
                    rows="4"
                />

                <div class="grid gap-5 lg:grid-cols-2">
                    <flux:textarea
                        wire:model="doctor_notes"
                        :label="__('doctor.diagnosis_form.special_notes')"
                        :placeholder="__('doctor.diagnosis_form.special_notes_placeholder')"
                        :description="__('doctor.diagnosis_form.special_notes_help')"
                        rows="4"
                    />

                    <flux:textarea
                        wire:model="treatment_plan"
                        :label="__('doctor.diagnosis_form.treatment_plan')"
                        :placeholder="__('doctor.diagnosis_form.treatment_plan_placeholder')"
                        rows="4"
                    />
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-end sm:px-5">
            <flux:button
                type="button"
                variant="ghost"
                :href="route('doctor.appointments.medical-history', $appointment)"
                wire:navigate
                class="w-full sm:w-auto"
            >
                {{ __('doctor.workflow.back_to_history') }}
            </flux:button>
            <flux:button
                type="submit"
                variant="primary"
                icon="arrow-right"
                class="w-full !rounded-full !bg-[#10B981] !px-6 !shadow-md !shadow-emerald-900/10 hover:!brightness-95 sm:w-auto"
            >
                {{ __('doctor.workflow.save_and_prescription') }}
            </flux:button>
        </div>
    </form>

    @include('partials.doctor-complete-appointment-modals')
</div>
