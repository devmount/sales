@php
    $heading = $this->getHeading();
    $filters = $this->getFilters();
@endphp

<x-filament-widgets::widget>
    <x-filament::section :heading="$heading">
        @if ($filters)
            <x-slot name="headerEnd">
                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="filter"
                    class="-my-2"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="filter"
                    >
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-slot>
        @endif
        {{ $this->taxInfolist }}
    </x-filament::section>
</x-filament-widgets::widget>
