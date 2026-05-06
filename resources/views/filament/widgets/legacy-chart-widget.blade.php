@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $isCollapsible = $this->isCollapsible();
    $type = $this->getType();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :heading="$heading" :collapsible="$isCollapsible" compact>
        <x-slot name="afterHeader">
            <div class="flex flex-shrink-0 items-center gap-2 sm:gap-3">
                <span
                    class="inline-flex min-h-[1.75rem] min-w-[2rem] items-center justify-center rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold tabular-nums text-white shadow-sm ring-1 ring-emerald-700/10 dark:bg-emerald-600 dark:text-white dark:ring-white/10">
                    {{ $this->badgePrimary() }}
                </span>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('This week') }}</span>
            </div>
        </x-slot>

        <div @if ($pollingInterval = $this->getPollingInterval()) wire:poll.{{ $pollingInterval }}="updateChartData" @endif>
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="{{ $type }}"
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            maxHeight: @js($maxHeight = $this->getMaxHeight()),
                            options: @js($this->getOptions()),
                            type: @js($type),
                        })"
                {{ (new ComponentAttributeBag)->color(ChartWidgetComponent::class, $color)->class([
                    'fi-wi-chart-canvas-ctn',
                    'fi-wi-chart-canvas-ctn-no-aspect-ratio' => filled($maxHeight),
                ]) }}>
                <canvas @if ($maxHeight) style="max-height: {{ $maxHeight }}" @endif x-ref="canvas"></canvas>

                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
