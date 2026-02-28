<?php

namespace App\Filament\Relations;

use App\Filament\Resources\PositionResource;
use App\Models\Position;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Livewire\Component;

class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(trans_choice('position', 1))
            ->heading(trans_choice('position', 2))
            ->defaultSort('started_at', 'asc')
            ->columns([
                TextColumn::make('description')
                    ->label(__('description'))
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn (Position $record): float => $record->duration)
                    ->weight(FontWeight::ExtraBold)
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (Position $record): string => $record->time_range),
                ToggleColumn::make('remote')
                    ->label(__('remote')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->afterFormFilled(function (Component $livewire) {
                        $mountedAction = $livewire->mountedActions[0] ?? null;
                        if (!$mountedAction) return;
                        $livewire->mountedActions[0]['data']['invoice_id'] = $this->ownerRecord->id;
                    })
                    ->schema(PositionResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge),
            ])
            ->recordActions([
                Action::make('copy')
                    ->icon('tabler-clipboard')
                    ->label('')
                    ->actionJs(function (Position $record) {
                        $text = str_replace("\n", "\\n", $record->description);
                        return <<<JS
                            window.navigator.clipboard.writeText('$text');
                            \$tooltip('Copied to clipboard', { timeout: 1500 });
                        JS;
                    }),
                EditAction::make()
                    ->icon('tabler-edit')
                    ->label('')
                    ->schema(PositionResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge),
                ReplicateAction::make()
                    ->icon('tabler-copy')
                    ->label('')
                    ->schema(PositionResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge),
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

    public static function getModelLabel(): string
    {
        return trans_choice('position', 1);
    }
}
