<?php

namespace App\Filament\Relations;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(trans_choice('position', 1))
            ->heading(trans_choice('invoice', 2))
            ->defaultSort('started_at', 'asc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('title'))
                    ->sortable()
                    ->description(fn (Invoice $record): string =>
                        ($record->invoiced_at ? __('invoicedAt') . ' ' . Carbon::parse($record->invoiced_at)->isoFormat('LL') : '') .
                        ($record->paid_at ? ', ' . __('paidAt') . ' ' . Carbon::parse($record->paid_at)->isoFormat('LL') : '')
                    )
                    ->tooltip(fn (Invoice $record): ?string => $record->description),
                TextColumn::make('price')
                    ->label(__('price'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (Invoice $record): string => $record->pricing_unit->getLabel()),
                TextColumn::make('net')
                    ->label(__('net'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->net)
                    ->description(fn (Invoice $record): string => $record->hours . ' ' . trans_choice('hour', $record->hours)),
                TextColumn::make('total')
                    ->label(__('total'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->final)
                    ->description(fn (Invoice $record): string => Number::currency($record->vat, 'eur') . ' ' . __('vat')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->schema(InvoiceResource::formFields(6, false))
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof Project)
                    ->mountUsing(function (Schema $schema) {
                        $schema->fill();
                        if ($this->getOwnerRecord() instanceof Project) {
                            $schema->fillPartially(['project_id' => $this->getOwnerRecord()->getKey()], ['project_id']);
                        }
                    })
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon('tabler-edit')
                        ->schema(InvoiceResource::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::ExtraLarge),
                    ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->schema(InvoiceResource::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::ExtraLarge),
                ])
                ->icon('tabler-dots-vertical')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->schema(InvoiceResource::formFields(6, false))
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof Project)
                    ->mountUsing(function (Schema $schema) {
                        $schema->fill();
                        if ($this->getOwnerRecord() instanceof Project) {
                            $schema->fillPartially(['project_id' => $this->getOwnerRecord()->getKey()], ['project_id']);
                        }
                    })
                    ->slideOver()
                    ->modalWidth(Width::ExtraLarge),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->paginated(false);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans_choice('invoice', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('invoice', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('invoice', 2);
    }
}
