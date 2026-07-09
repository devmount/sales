<x-filament-widgets::widget>
    <x-filament::section class="fi-section" :description="$description" :heading="$heading">
        <div class="project-progress">
            @foreach ($projects as $p)
                <a
                    href="/invoices/{{ $p->invoices()->latest()?->first()?->id }}/edit"
                    data-percent="{{ $p->progress_percent }}"
                >
                    <progress
                        value="{{ $p->progress }}"
                        max="100"
                        style="
                            --accent: {{ $p->client->color }};
                            --end-border-radius: {{ $p->progress > 99 ? '.4rem' : '.25rem' }};
                        "
                        x-tooltip="{ content: '{{ $p->tooltip }}', theme: $store.theme, allowHTML: true }"
                    >
                        {{ $p->progress_percent }}
                    </progress>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
