<x-filament-widgets::widget>
    <x-filament::section class="fi-section" :description="$description" :heading="$heading">
        <x-slot name="afterHeader">
            <div class="event-matrix-controls">
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

        <div class="event-matrix mode-{{ $mode }}">
            @for ($i = 0; $i < $offset; $i++)
                <span class="empty"></span>
            @endfor
            @foreach ($days as $day)
                <span
                    wire:key="{{ $mode }}-{{ $day['date']->format('Y-m-d') }}"
                    data-level="{{ $day['level'] }}"
                    @class(['negative' => $day['negative'], 'offtime' => $day['offtime']])
                    @if ($day['offtime']) style="--offtime: {{ $day['offtime']->hexColor() }}" @endif
                    x-tooltip="{ content: @js($day['tooltip']), theme: $store.theme, allowHTML: true }"
                ></span>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
