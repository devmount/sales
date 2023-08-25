<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Project;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label(__('All')),
            'active' => Tab::make()
                ->label(__('Active'))
                ->badge(Project::query()->where('start_at', '<=', now())->where('due_at', '>=', now())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('start_at', '<=', now())->where('due_at', '>=', now())),
            'finished' => Tab::make()
                ->label(__('Finished'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('due_at', '<=', now())),
            'aborted' => Tab::make()
                ->label(__('Aborted'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('aborted', true)),
        ];
    }
}
