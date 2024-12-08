@if (filled($brand = filament()->getBrandName()))
    <div
        {{
            $attributes->class([
                'fi-logo text-xl font-bold leading-5 tracking-tight text-gray-950 dark:text-white',
            ])
        }}
    >
        {{ $brand }}
        <span class="text-gray-400 text-sm font-normal">
            &nbsp;v{{ config('app.version') }}
        </span>
    </div>
@endif
