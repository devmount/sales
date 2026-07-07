<x-filament-widgets::widget>
    <x-filament::section class="fi-section" :description="$description" :heading="$heading">
        <div class="project-progress">
            @foreach ($projects as $p)
                <progress
                    value="{{ $p->progress }}"
                    max="100"
                    @style(['border-color: ' . $p->client->color])
                >
                    {{ $p->progress_percent }}
                </progress>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
