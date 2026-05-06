@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $type = $this->getType();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section compact>
        <x-slot name="heading">
            <div class="flex w-full min-w-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <span class="text-base font-semibold leading-tight text-gray-950 dark:text-white">
                    {{ __('Top 20 Sales Doctors') }}
                </span>
                <div class="w-full shrink-0 md:w-auto md:max-w-[14rem]">
                    <x-filament::input.wrapper class="w-full">
                        <x-filament::input.select wire:model.live="month" class="w-full">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}">
                                    {{ \Carbon\Carbon::createFromDate(now()->year, $m, 1)->format('F') }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-slot>

        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
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
                {{
                    (new ComponentAttributeBag)
                        ->color(ChartWidgetComponent::class, $color)
                        ->class([
                            'fi-wi-chart-canvas-ctn',
                            'fi-wi-chart-canvas-ctn-no-aspect-ratio' => filled($maxHeight),
                        ])
                }}
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight)
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span
                    x-ref="backgroundColorElement"
                    class="fi-wi-chart-bg-color"
                ></span>

                <span
                    x-ref="borderColorElement"
                    class="fi-wi-chart-border-color"
                ></span>

                <span
                    x-ref="gridColorElement"
                    class="fi-wi-chart-grid-color"
                ></span>

                <span
                    x-ref="textColorElement"
                    class="fi-wi-chart-text-color"
                ></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
