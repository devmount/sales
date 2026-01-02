<?php

namespace App\Filament\Relations;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn (Project $record): ?string => $record->description),
                TextColumn::make('date_range')
                    ->label(__('dateRange'))
                    ->state(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->isoFormat('ll') . ' - ' . ($record->due_at ? Carbon::parse($record->due_at)->isoFormat('ll') : '∞')
                    ),
                TextColumn::make('scope')
                    ->label(__('scope'))
                    ->state(fn (Project $record): string => $record->scope_range),
                TextColumn::make('price_per_unit')
                    ->label(__('price'))
                    ->state(fn (Project $record): string => $record->price_per_unit),
                TextColumn::make('progress')
                    ->label(__('progress'))
                    ->state(fn (Project $record): string => $record->hours_with_label),
                TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('updatedAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_at', 'desc')
            ->headerActions([
                Action::make('create')
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->afterFormFilled(function (Component $livewire) {
                        $livewire->mountedActions[0]['data']['client_id'] = $this->ownerRecord->id;
                    })
                    ->schema(ProjectResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::Large),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon('tabler-edit')
                        ->schema(ProjectResource::formFields(useSection: false))
                        ->slideOver()
                        ->modalWidth(Width::Large),
                    ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->schema(ProjectResource::formFields(useSection: false))
                        ->slideOver()
                        ->modalWidth(Width::Large),
                ])
                ->icon('tabler-dots-vertical')
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans_choice('project', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('project', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('project', 2);
    }
}
