@props(['appointment', 'active'])

@php
    /** @var \App\Models\Appointment $appointment */
    $workflow = app(\App\Support\DoctorAppointmentWorkflow::class);
    $steps = $workflow->steps($appointment, $active);
    $showWorkflow = in_array($appointment->status, ['in_process', 'completed'], true);
    $nextRoute = $workflow->nextStepRoute($appointment);
    $appointment->loadMissing(['diagnosis', 'medications']);
    $readyToComplete = $appointment->status === 'in_process'
        && $appointment->diagnosis !== null
        && ($appointment->prescription_not_needed || $appointment->medications->isNotEmpty());

    $completedCount = collect($steps)->where('complete', true)->count();
    $totalSteps = count($steps);
    $progressPercent = $totalSteps > 0 ? (int) round(($completedCount / $totalSteps) * 100) : 0;
@endphp

@if ($showWorkflow && count($steps) > 0)
    <div class="doctor-workflow-card mt-4 rounded-xl border border-zinc-200/80 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-[#10B981] ring-1 ring-emerald-100">
                        <flux:icon name="clipboard-document-check" variant="mini" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-zinc-900 sm:text-base">{{ __('doctor.workflow.title') }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-zinc-500 sm:text-sm">{{ __('doctor.workflow.subtitle') }}</p>
                        <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                            {{ $completedCount }}/{{ $totalSteps }} · {{ $progressPercent }}%
                        </p>
                    </div>
                </div>

                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-[#10B981] to-[#047857] transition-all duration-500"
                        style="width: {{ max($progressPercent, 6) }}%"
                        role="progressbar"
                        aria-valuenow="{{ $progressPercent }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
            </div>

            <div class="shrink-0 lg:pt-1">
                @if ($appointment->status === 'in_process' && $nextRoute)
                    <flux:button
                        :href="$nextRoute"
                        wire:navigate
                        size="sm"
                        variant="primary"
                        icon="arrow-right"
                        class="w-full !rounded-full !bg-[#10B981] !px-5 !shadow-md !shadow-emerald-900/10 hover:!brightness-95 sm:w-auto"
                    >
                        {{ __('doctor.workflow.continue') }}
                    </flux:button>
                @elseif ($readyToComplete)
                    <flux:button
                        type="button"
                        size="sm"
                        variant="primary"
                        icon="check-circle"
                        class="w-full !rounded-full !bg-[#10B981] !px-5 !shadow-md !shadow-emerald-900/10 hover:!brightness-95 sm:w-auto"
                        wire:click="requestCompleteAppointment"
                    >
                        {{ __('doctor.workflow.mark_complete') }}
                    </flux:button>
                @elseif ($appointment->status === 'completed' && $nextRoute)
                    <flux:button
                        :href="$nextRoute"
                        wire:navigate
                        size="sm"
                        variant="primary"
                        icon="calendar-days"
                        class="w-full !rounded-full !bg-[#10B981] !px-5 !shadow-md !shadow-emerald-900/10 hover:!brightness-95 sm:w-auto"
                    >
                        {{ __('doctor.workflow.schedule_follow_up') }}
                    </flux:button>
                @endif
            </div>
        </div>

        <ol class="doctor-workflow-steps mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-stretch sm:gap-2">
            @foreach ($steps as $index => $step)
                @php
                    $isComplete = $step['complete'];
                    $isCurrent = $step['current'];

                    $pillClass = match (true) {
                        $isCurrent => 'border-[#10B981] bg-[#10B981] text-white shadow-md shadow-emerald-900/15',
                        $isComplete => 'border-emerald-200 bg-emerald-50 text-emerald-900 hover:border-emerald-300',
                        default => 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:text-zinc-900',
                    };

                    $badgeClass = match (true) {
                        $isCurrent => 'bg-white/20 text-white',
                        $isComplete => 'bg-[#10B981] text-white',
                        default => 'bg-zinc-100 text-zinc-600',
                    };

                    $labelClass = match (true) {
                        $isCurrent => 'text-white',
                        $isComplete => 'text-emerald-900',
                        default => 'text-zinc-600',
                    };
                @endphp

                <li class="min-w-0 sm:flex-1">
                    @if ($step['key'] === 'complete' && $appointment->status === 'in_process' && ! $isComplete)
                        <button
                            type="button"
                            wire:click="requestCompleteAppointment"
                            @class([
                                'doctor-workflow-step-pill flex w-full min-w-0 items-center justify-center gap-2 rounded-full border px-3 py-2.5 transition sm:px-4',
                                $pillClass,
                            ])
                        >
                            <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold', $badgeClass])>
                                {{ $index + 1 }}
                            </span>
                            <span @class(['truncate text-xs font-semibold sm:text-sm', $labelClass])>
                                {{ $step['label'] }}
                            </span>
                        </button>
                    @elseif ($step['route'])
                        <a
                            href="{{ $step['route'] }}"
                            wire:navigate
                            @class([
                                'doctor-workflow-step-pill flex w-full min-w-0 items-center justify-center gap-2 rounded-full border px-3 py-2.5 transition sm:px-4',
                                $pillClass,
                            ])
                        >
                            <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold', $badgeClass])>
                                @if ($isComplete)
                                    <flux:icon name="check" variant="mini" class="size-4" />
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            <span @class(['truncate text-xs font-semibold sm:text-sm', $labelClass])>
                                {{ $step['label'] }}
                            </span>
                        </a>
                    @else
                        <div @class([
                            'doctor-workflow-step-pill flex w-full min-w-0 items-center justify-center gap-2 rounded-full border px-3 py-2.5 sm:px-4',
                            $pillClass,
                        ])>
                            <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold', $badgeClass])>
                                @if ($isComplete)
                                    <flux:icon name="check" variant="mini" class="size-4" />
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            <span @class(['truncate text-xs font-semibold sm:text-sm', $labelClass])>
                                {{ $step['label'] }}
                            </span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
@endif
