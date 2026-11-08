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
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

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
                    ->formatStateUsing(fn(string $state): string => nl2br($state))
                    ->html(),
                TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn(Position $record): float => $record->duration)
                    ->weight(FontWeight::ExtraBold)
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn(Position $record): string => $record->time_range),
                ToggleColumn::make('remote')
                    ->label(__('remote')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->schema(fn() => [...PositionResource::formFields(useSection: false), $this->descriptionHistory()])
                    ->mountUsing(function (Schema $schema) {
                        $schema->fill();
                        $schema->fillPartially(['invoice_id' => $this->getOwnerRecord()->getKey()], ['invoice_id']);
                    })
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->after(fn() => $this->dispatch('refresh-page')),
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
                    ->modalWidth(Width::TwoExtraLarge)
                    ->after(fn() => $this->dispatch('refresh-page')),
                ReplicateAction::make()
                    ->icon('tabler-copy')
                    ->label('')
                    ->schema(PositionResource::formFields(useSection: false))
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->after(fn() => $this->dispatch('refresh-page')),
                DeleteAction::make()
                    ->icon('tabler-trash')
                    ->label('')
                    ->requiresConfirmation()
                    ->after(fn() => $this->dispatch('refresh-page')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash')->after(fn() => $this->dispatch('refresh-page')),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()->icon('tabler-plus')->after(fn() => $this->dispatch('refresh-page')),
            ])
            ->paginated(false);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('position', 1);
    }

    /**
     * Section listing recent descriptions of the client, each can be used for the description field
     */
    private function descriptionHistory(): Section
    {
        $descriptions = $this->getOwnerRecord()->recentPositionDescriptions();

        return Section::make(__('recentDescriptions'))
            ->collapsed()
            ->hidden(empty($descriptions))
            ->schema(array_map(
                fn(int $i, string $description) => TextEntry::make("history_$i")
                    ->hiddenLabel()
                    ->size(TextSize::ExtraSmall)
                    ->state($description)
                    ->formatStateUsing(fn(string $state): string => nl2br(e($state)))
                    ->html()
                    ->afterContent(
                        Action::make('useDescription')
                            ->icon('tabler-arrow-bar-to-up')
                            ->iconButton()
                            ->tooltip(__('useDescription'))
                            ->action(fn(Set $set) => $set('description', $description)),
                    ),
                array_keys($descriptions),
                $descriptions,
            ));
    }
}
