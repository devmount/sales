<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('tabler-plus')
                ->schema(ProjectResource::formFields(6, false))
                ->slideOver()
                ->modalWidth(Width::Large),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make()
                ->label(__('active'))
                ->badge(Project::active()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->active()),
            'upcoming' => Tab::make()
                ->label(__('upcoming'))
                ->badge(Project::upcoming()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->upcoming()),
            'finished' => Tab::make()
                ->label(__('finished'))
                ->badge(Project::finished()->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->finished()),
            'aborted' => Tab::make()
                ->label(__('aborted'))
                ->badge(Project::aborted()->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->aborted()),
            'all' => Tab::make()
                ->label(__('all')),
        ];
    }
}
