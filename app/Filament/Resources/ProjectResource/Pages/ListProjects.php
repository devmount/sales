<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('tabler-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make()
                ->label(__('active'))
                ->badge(Project::query()->where('start_at', '<=', now())->where('due_at', '>=', now())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('start_at', '<=', now())->where('due_at', '>=', now())),
            'finished' => Tab::make()
                ->label(__('finished'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('due_at', '<=', now())),
            'aborted' => Tab::make()
                ->label(__('aborted'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('aborted', true)),
            'all' => Tab::make()
                ->label(__('all')),
        ];
    }
}
