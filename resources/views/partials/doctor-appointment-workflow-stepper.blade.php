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
@endphp

@if ($showWorkflow && count($steps) > 0)
    <div class="mt-4 rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50/80 via-white to-white p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-zinc-900">{{ __('doctor.workflow.title') }}</p>
                <p class="mt-0.5 text-xs text-zinc-600">{{ __('doctor.workflow.subtitle') }}</p>
            </div>
            @if ($appointment->status === 'in_process' && $nextRoute)
                <flux:button
                    :href="$nextRoute"
                    wire:navigate
                    size="sm"
                    variant="primary"
                    icon="arrow-right"
                    class="!bg-[#10B981] hover:!brightness-95"
                >
                    {{ __('doctor.workflow.continue') }}
                </flux:button>
            @elseif ($readyToComplete)
                <flux:button
                    type="button"
                    size="sm"
                    variant="primary"
                    icon="check-circle"
                    class="!bg-[#10B981] hover:!brightness-95"
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
                    class="!bg-[#10B981] hover:!brightness-95"
                >
                    {{ __('doctor.workflow.schedule_follow_up') }}
                </flux:button>
            @endif
        </div>

        <ol class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-stretch sm:gap-0">
            @foreach ($steps as $index => $step)
                @php
                    $isLast = $index === count($steps) - 1;
                    $isComplete = $step['complete'];
                    $isCurrent = $step['current'];
                @endphp
                <li class="flex min-w-0 flex-1 items-center gap-2 sm:gap-0">
                    <div @class([
                        'flex min-w-0 flex-1 items-center gap-2 rounded-xl border px-3 py-2.5 transition sm:rounded-none sm:border-0 sm:px-2 sm:py-0',
                        'border-[#10B981]/35 bg-white shadow-sm' => $isCurrent,
                        'border-zinc-200/80 bg-white/60' => ! $isCurrent && ! $isComplete,
                        'border-emerald-200/60 bg-emerald-50/50' => $isComplete && ! $isCurrent,
                    ])>
                        <span @class([
                            'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            'bg-[#10B981] text-white shadow-sm shadow-emerald-900/20' => $isComplete || $isCurrent,
                            'bg-zinc-200 text-zinc-600' => ! $isComplete && ! $isCurrent,
                        ])>
                            @if ($isComplete)
                                <flux:icon name="check" variant="mini" class="size-4" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            @if ($step['key'] === 'complete' && $appointment->status === 'in_process' && ! $isComplete)
                                <button
                                    type="button"
                                    wire:click="requestCompleteAppointment"
                                    class="truncate text-left text-xs font-semibold text-[#047857] hover:text-[#10B981] sm:text-sm"
                                >
                                    {{ $step['label'] }}
                                </button>
                            @elseif ($step['route'])
                                <a
                                    href="{{ $step['route'] }}"
                                    wire:navigate
                                    @class([
                                        'block truncate text-xs font-semibold sm:text-sm',
                                        'text-[#047857]' => $isCurrent || $isComplete,
                                        'text-zinc-600 hover:text-[#10B981]' => ! $isCurrent && ! $isComplete,
                                    ])
                                >
                                    {{ $step['label'] }}
                                </a>
                            @else
                                <span @class([
                                    'block truncate text-xs font-semibold sm:text-sm',
                                    'text-[#047857]' => $isComplete,
                                    'text-zinc-500' => ! $isComplete,
                                ])>
                                    {{ $step['label'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @if (! $isLast)
                        <flux:icon
                            name="chevron-right"
                            variant="mini"
                            class="hidden size-4 shrink-0 text-zinc-300 sm:mx-1 sm:block rtl:rotate-180"
                        />
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
@endif
