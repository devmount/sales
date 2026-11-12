<x-filament-widgets::widget>
    <x-filament::section class="fi-section" :description="$description" :heading="$heading">
        <x-slot name="afterHeader">
            <div class="event-matrix-controls">
                <label>
                    <x-filament::toggle state="$wire.$entangle('offtimes', true)" />
                    {{ trans_choice('offtime', 2) }}
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="mode">
                        @foreach ($modes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="year">
                        @foreach ($years as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </x-slot>

        <div class="event-matrix mode-{{ $mode }}" style="--weeks: {{ $lastCol - 1 }}">
            @foreach ($months as $col => $label)
                <small class="month" style="grid-area: 1 / {{ $col }}">{{ $label }}</small>
            @endforeach
            @foreach ($weekdays as $row => $label)
                <small class="weekday" style="grid-area: {{ $row }} / 1">{{ $label }}</small>
            @endforeach
            @foreach ($days as $day)
                <span
                    wire:key="{{ $mode }}-{{ $day['date']->format('Y-m-d') }}"
                    data-level="{{ $day['level'] }}"
                    @class(['negative' => $day['negative'], 'offtime' => $day['offtime']])
                    @style([
                        "grid-area: {$day['row']} / {$day['col']}",
                        "--offtime: {$day['offtime']?->hexColor()}" => $day['offtime'],
                    ])
                    x-tooltip="{ content: @js($day['tooltip']), theme: $store.theme, allowHTML: true }"
                ></span>
            @endforeach
            <div class="legend" style="grid-area: 9 / 2 / auto / {{ $lastCol + 1 }}">
                <small class="sum">{{ __('total') }}: <code>{{ $sum }}</code></small>
                <small>{{ __('less') }}</small>
                @if ($mode === 'total')
                    @foreach (range(4, 1) as $level)
                        <span class="negative" data-level="{{ $level }}"></span>
                    @endforeach
                @endif
                @foreach (range(0, 4) as $level)
                    <span data-level="{{ $level }}"></span>
                @endforeach
                <small>{{ __('more') }}</small>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
