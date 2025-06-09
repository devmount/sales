<?php

namespace App\Filament\Relations;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Livewire\Component;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Columns\TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn (Project $record): ?string => $record->description),
                Columns\TextColumn::make('date_range')
                    ->label(__('dateRange'))
                    ->state(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->isoFormat('ll') . ' - ' . ($record->due_at ? Carbon::parse($record->due_at)->isoFormat('ll') : '∞')
                    ),
                Columns\TextColumn::make('scope')
                    ->label(__('scope'))
                    ->state(fn (Project $record): string => $record->scope_range),
                Columns\TextColumn::make('price_per_unit')
                    ->label(__('price'))
                    ->state(fn (Project $record): string => $record->price_per_unit),
                Columns\TextColumn::make('progress')
                    ->label(__('progress'))
                    ->state(fn (Project $record): string => $record->hours_with_label),
                Columns\TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('updated_at')
                    ->label(__('updatedAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_at', 'desc')
            ->headerActions([
                Actions\Action::make('create')
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->beforeFormFilled(function (Component $livewire) {
                        $livewire->mountedTableActionsData[0]['client_id'] = $this->ownerRecord->id;
                    })
                    ->form(ProjectResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(MaxWidth::Large),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->icon('tabler-edit')
                        ->form(ProjectResource::formFields(useSection: false))
                        ->slideOver()
                        ->modalWidth(MaxWidth::Large),
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->form(ProjectResource::formFields(useSection: false))
                        ->slideOver()
                        ->modalWidth(MaxWidth::Large),
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
