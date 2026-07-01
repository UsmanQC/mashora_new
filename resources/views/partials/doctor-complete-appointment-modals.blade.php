<flux:modal wire:model.self="showCompleteAppointmentModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
    <div class="flex flex-col items-center px-6 py-8 text-center sm:px-10 sm:py-10">
        <div
            class="mb-6 flex size-24 shrink-0 items-center justify-center rounded-full border-[3px] border-[#f8bb86] bg-amber-50/60"
            aria-hidden="true"
        >
            <span class="text-5xl font-bold leading-none text-[#f8bb86]">!</span>
        </div>

        <flux:heading size="xl" class="font-bold text-[#545454]">
            {{ __('doctor.complete_modal.title') }}
        </flux:heading>

        <flux:text class="mt-3 max-w-[20rem] leading-relaxed text-[#595959]">
            {{ __('doctor.complete_modal.body') }}
        </flux:text>

        <div class="mt-8 flex w-full max-w-sm flex-row gap-3 sm:justify-center">
            <flux:button
                type="button"
                class="flex-1 !border-0 bg-[#7066e0] text-white shadow-sm hover:brightness-105 sm:min-h-11"
                variant="primary"
                wire:click="confirmCompleteAppointment"
            >
                {{ __('doctor.complete_modal.confirm') }}
            </flux:button>
            <flux:button
                type="button"
                class="flex-1 !border-0 bg-[#6e7881] text-white shadow-sm hover:bg-[#5d656c] sm:min-h-11"
                variant="ghost"
                wire:click="dismissCompleteAppointmentModal"
            >
                {{ __('doctor.complete_modal.cancel') }}
            </flux:button>
        </div>
    </div>
</flux:modal>

<flux:modal wire:model.self="showDiagnosisRequiredModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
    <div class="flex flex-col items-center px-6 py-8 text-center sm:px-10 sm:py-10">
        <div
            class="mb-6 flex size-24 shrink-0 items-center justify-center rounded-full border-[3px] border-[#f8bb86] bg-amber-50/60"
            aria-hidden="true"
        >
            <span class="text-5xl font-bold leading-none text-[#f8bb86]">!</span>
        </div>

        <flux:heading size="xl" class="font-bold text-[#545454]">
            {{ __('doctor.complete_flow.diagnosis_title') }}
        </flux:heading>

        <flux:text class="mt-3 max-w-[20rem] leading-relaxed text-[#595959]">
            {{ __('doctor.complete_flow.diagnosis_body') }}
        </flux:text>

        <div class="mt-8 flex w-full max-w-sm flex-col gap-3 sm:justify-center">
            @if (isset($appointment) && $appointment instanceof \App\Models\Appointment)
                <flux:button
                    :href="route('doctor.appointments.diagnosis', $appointment)"
                    wire:navigate
                    class="w-full !border-0 bg-[#7066e0] text-white shadow-sm hover:brightness-105 sm:min-h-11"
                    variant="primary"
                >
                    {{ __('doctor.workspace.tab_diagnosis') }}
                </flux:button>
            @endif
            <flux:button
                type="button"
                class="w-full !border-0 bg-[#6e7881] text-white shadow-sm hover:bg-[#5d656c] sm:min-h-11"
                variant="ghost"
                wire:click="dismissDiagnosisRequiredModal"
            >
                {{ __('doctor.complete_flow.ok') }}
            </flux:button>
        </div>
    </div>
</flux:modal>

<flux:modal wire:model.self="showPrescriptionRequiredModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
    <div class="flex flex-col items-center px-6 py-8 text-center sm:px-10 sm:py-10">
        <div
            class="mb-6 flex size-24 shrink-0 items-center justify-center rounded-full border-[3px] border-[#f8bb86] bg-amber-50/60"
            aria-hidden="true"
        >
            <span class="text-5xl font-bold leading-none text-[#f8bb86]">!</span>
        </div>

        <flux:heading size="xl" class="font-bold text-[#545454]">
            {{ __('doctor.complete_flow.prescription_title') }}
        </flux:heading>

        <flux:text class="mt-3 max-w-[20rem] leading-relaxed text-[#595959]">
            {{ __('doctor.complete_flow.prescription_body') }}
        </flux:text>

        <div class="mt-8 flex w-full max-w-sm flex-col gap-3 sm:justify-center">
            @if (isset($appointment) && $appointment instanceof \App\Models\Appointment)
                <flux:button
                    :href="route('doctor.appointments.prescription', $appointment)"
                    wire:navigate
                    class="w-full !border-0 bg-[#7066e0] text-white shadow-sm hover:brightness-105 sm:min-h-11"
                    variant="primary"
                >
                    {{ __('doctor.workspace.tab_prescription') }}
                </flux:button>
            @endif
            <flux:button
                type="button"
                class="w-full !border-0 bg-[#6e7881] text-white shadow-sm hover:bg-[#5d656c] sm:min-h-11"
                variant="ghost"
                wire:click="dismissPrescriptionRequiredModal"
            >
                {{ __('doctor.complete_flow.ok') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
