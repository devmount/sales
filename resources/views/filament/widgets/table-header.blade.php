<header class="table-header">
    <h2>{{ $heading }}</h2>
    <div>
        @if ($options)
            <div class="table-filter">
                <x-filament::input.wrapper wire:target="filter">
                    <x-filament::input.select wire:model.live="filter">
                        @foreach ($options as $value)
                            <option value="{{ $value }}">
                                {{ $value }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        @endif
    </div>
</header>
