<?php

namespace App\Filament\Relations;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
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
                Columns\TextColumn::make('title')
                    ->label(__('title'))
                    ->sortable()
                    ->description(fn (Invoice $record): string =>
                        ($record->invoiced_at ? __('invoicedAt') . ' ' . Carbon::parse($record->invoiced_at)->isoFormat('LL') : '') .
                        ($record->paid_at ? ', ' . __('paidAt') . ' ' . Carbon::parse($record->paid_at)->isoFormat('LL') : '')
                    )
                    ->tooltip(fn (Invoice $record): ?string => $record->description),
                Columns\TextColumn::make('price')
                    ->label(__('price'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (Invoice $record): string => $record->pricing_unit->getLabel()),
                Columns\TextColumn::make('net')
                    ->label(__('net'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->net)
                    ->description(fn (Invoice $record): string => $record->hours . ' ' . trans_choice('hour', $record->hours)),
                Columns\TextColumn::make('total')
                    ->label(__('total'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Invoice $record): float => $record->final)
                    ->description(fn (Invoice $record): string => Number::currency($record->vat, 'eur') . ' ' . __('vat')),
            ])
            ->headerActions([
                Actions\Action::make('create')
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->form(InvoiceResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::ThreeExtraLarge),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->icon('tabler-edit')
                        ->form(InvoiceResource::formFields())
                        ->slideOver()
                        ->modalWidth(MaxWidth::ThreeExtraLarge),
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->form(InvoiceResource::formFields())
                        ->slideOver()
                        ->modalWidth(MaxWidth::ThreeExtraLarge),
                ])
                ->icon('tabler-dots-vertical')
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                Actions\Action::make('create')
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->form(InvoiceResource::formFields())
                    ->slideOver()
                    ->modalWidth(MaxWidth::ThreeExtraLarge),
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
