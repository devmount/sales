<?php

namespace App\Filament\Relations;

use App\Filament\Resources\EstimateResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class EstimatesRelationManager extends RelationManager
{
    protected static string $relationship = 'estimates';

    public function table(Table $table): Table
    {
        return $table
            ->heading(trans_choice('estimate', 2))
            ->defaultSort('weight', 'asc')
            ->reorderable('weight')
            ->columns([
                TextColumn::make('title')
                    ->label(__('title')),
                TextColumn::make('description')
                    ->label(__('description'))
                    ->copyable()
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->numeric()
                    ->weight(FontWeight::ExtraBold)
                    ->summarize(Sum::make()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->afterFormFilled(function (Component $livewire) {
                        $livewire->mountedActions[0]['data']['project_id'] = $this->ownerRecord->id;
                    })
                    ->schema(EstimateResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::Large),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('tabler-edit')
                    ->label('')
                    ->schema(EstimateResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::Large),
                ReplicateAction::make()
                    ->icon('tabler-copy')
                    ->label('')
                    ->schema(EstimateResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::Large),
                DeleteAction::make()
                    ->icon('tabler-trash')
                    ->label('')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()->icon('tabler-plus'),
            ])
            ->paginated(false);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans_choice('estimate', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('estimate', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('estimate', 2);
    }
}
