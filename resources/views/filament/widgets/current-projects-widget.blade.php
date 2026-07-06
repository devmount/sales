<x-filament-widgets::widget>
    <x-filament::section class="fi-section" :description="$description" :heading="$heading">
        <div class="project-progress">
            @foreach ($projects as $p)
                <div @style(['border-color: ' . $p->client->color])>
                    {{ $p->progress_percent }}
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
