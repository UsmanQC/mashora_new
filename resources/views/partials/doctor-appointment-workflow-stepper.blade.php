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
                        class="w-full !rounded-xl !bg-[#10B981] !px-5 !shadow-md !shadow-emerald-900/10 hover:!brightness-95 sm:w-auto"
                    >
                        {{ __('doctor.workflow.continue') }}
                    </flux:button>
                @elseif ($readyToComplete)
                    <flux:button
                        type="button"
                        size="sm"
                        variant="primary"
                        icon="check-circle"
                        class="w-full !rounded-xl !bg-[#10B981] !px-5 !shadow-md !shadow-emerald-900/10 hover:!brightness-95 sm:w-auto"
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
                        class="w-full !rounded-xl !bg-[#10B981] !px-5 !shadow-md !shadow-emerald-900/10 hover:!brightness-95 sm:w-auto"
                    >
                        {{ __('doctor.workflow.schedule_follow_up') }}
                    </flux:button>
                @endif
            </div>
        </div>

        <ol class="doctor-workflow-steps mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-none lg:flex lg:items-start lg:justify-between">
            @foreach ($steps as $index => $step)
                @php
                    $isLast = $index === count($steps) - 1;
                    $isComplete = $step['complete'];
                    $isCurrent = $step['current'];
                @endphp
                <li @class([
                    'doctor-workflow-step relative flex min-w-0 items-center gap-3 rounded-xl border px-3 py-3 transition',
                    'border-[#10B981]/30 bg-emerald-50/50 ring-1 ring-[#10B981]/10' => $isCurrent,
                    'border-emerald-100 bg-emerald-50/30' => $isComplete && ! $isCurrent,
                    'border-zinc-200/80 bg-zinc-50/50' => ! $isComplete && ! $isCurrent,
                    'lg:flex-1 lg:flex-col lg:items-center lg:gap-2 lg:border-0 lg:bg-transparent lg:px-2 lg:py-0 lg:text-center',
                ])>
                    @if (! $isLast)
                        <span
                            class="doctor-workflow-step-connector hidden lg:absolute lg:top-4 lg:block lg:h-px lg:bg-zinc-200"
                            aria-hidden="true"
                        ></span>
                    @endif

                    <span @class([
                        'relative z-[1] flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-2 ring-white',
                        'bg-[#10B981] text-white shadow-sm shadow-emerald-900/20' => $isComplete || $isCurrent,
                        'bg-zinc-200 text-zinc-600' => ! $isComplete && ! $isCurrent,
                    ])>
                        @if ($isComplete)
                            <flux:icon name="check" variant="mini" class="size-4" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>

                    <div class="min-w-0 flex-1 lg:flex-none">
                        @if ($step['key'] === 'complete' && $appointment->status === 'in_process' && ! $isComplete)
                            <button
                                type="button"
                                wire:click="requestCompleteAppointment"
                                class="block truncate text-left text-xs font-semibold text-[#047857] transition hover:text-[#10B981] sm:text-sm lg:text-center"
                            >
                                {{ $step['label'] }}
                            </button>
                        @elseif ($step['route'])
                            <a
                                href="{{ $step['route'] }}"
                                wire:navigate
                                @class([
                                    'block truncate text-xs font-semibold transition sm:text-sm lg:text-center',
                                    'text-[#047857]' => $isCurrent,
                                    'text-emerald-800/80' => $isComplete && ! $isCurrent,
                                    'text-zinc-600 hover:text-[#10B981]' => ! $isCurrent && ! $isComplete,
                                ])
                            >
                                {{ $step['label'] }}
                            </a>
                        @else
                            <span @class([
                                'block truncate text-xs font-semibold sm:text-sm lg:text-center',
                                'text-[#047857]' => $isComplete || $isCurrent,
                                'text-zinc-500' => ! $isComplete && ! $isCurrent,
                            ])>
                                {{ $step['label'] }}
                            </span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endif
