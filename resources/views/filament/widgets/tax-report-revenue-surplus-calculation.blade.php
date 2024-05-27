<x-filament-widgets::widget>
    <x-filament::section :heading="$this->getHeading()">
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
                    @foreach ($this->getFilters() as $value)
                        <option value="{{ $value }}">
                            {{ $value }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>
        {{ $this->infolist }}
    </x-filament::section>
</x-filament-widgets::widget>
