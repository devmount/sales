<header class="table-header">
    <div class="table-header-heading">
        <h2>{{ $heading }}</h2>
        @if ($description ?? null)
            <p class="table-header-description">{{ $description }}</p>
        @endif
    </div>
    <div class="table-controls">
        @if ($options)
            <div class="table-filter">
                <x-filament::input.wrapper wire:target="filter">
                    <x-filament::input.select wire:model.live="filter">
                        @foreach ($options as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        @endif
        @if ($actions)
            <div class="table-actions">
                @foreach ($actions as $action)
                    {{ $action }}
                @endforeach
            </div>
        @endif
    </div>
</header>
