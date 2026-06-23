<?php

use App\Models\Appointment;
use App\Models\Diagnosis;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Diagnosis')] class extends Component
{
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

<div class="space-y-8">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'diagnosis'])

    <div>
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.diagnosis_form.title') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600">{{ __('doctor.diagnosis_form.subtitle') }}</flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
            <flux:radio.group wire:model="marital_status" :label="__('doctor.diagnosis_form.is_patient_married')" variant="segmented">
                <flux:radio value="married" :label="__('doctor.diagnosis_form.yes')" />
                <flux:radio value="unmarried" :label="__('doctor.diagnosis_form.no')" />
            </flux:radio.group>
        </div>

        <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
            <div class="grid gap-5">
                <flux:input
                    wire:model="diagnosis_name"
                    :label="__('doctor.diagnosis_form.diagnosis_name')"
                    required
                />

                <flux:textarea
                    wire:model="medical_history"
                    :label="__('doctor.diagnosis_form.medical_history')"
                    :placeholder="__('doctor.diagnosis_form.medical_history_placeholder')"
                    rows="3"
                />

                <div class="grid gap-5 lg:grid-cols-2">
                    <flux:textarea
                        wire:model="doctor_notes"
                        :label="__('doctor.diagnosis_form.special_notes')"
                        :placeholder="__('doctor.diagnosis_form.special_notes_placeholder')"
                        :description="__('doctor.diagnosis_form.special_notes_help')"
                        rows="3"
                    />

                    <flux:textarea
                        wire:model="treatment_plan"
                        :label="__('doctor.diagnosis_form.treatment_plan')"
                        :placeholder="__('doctor.diagnosis_form.treatment_plan_placeholder')"
                        rows="3"
                    />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <flux:button
                type="button"
                variant="ghost"
                :href="route('doctor.dashboard')"
                wire:navigate
            >
                {{ __('doctor.diagnosis_form.cancel') }}
            </flux:button>
            <flux:button
                type="submit"
                variant="primary"
                class="!bg-[#10B981] hover:!brightness-95"
            >
                {{ __('doctor.diagnosis_form.save') }}
            </flux:button>
        </div>
    </form>
</div>
