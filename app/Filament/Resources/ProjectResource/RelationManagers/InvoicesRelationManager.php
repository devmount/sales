<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Forms\Components;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers;
use Carbon\Carbon;

use function Filament\Support\format_money;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    // public function form(Form $form): Form
    // {
    //     return $form
    //         ->columns(12)
    //         ->schema([
    //             DateTimePicker::make('started_at')
    //                 ->label(__('startedAt'))
    //                 ->weekStartsOnMonday()
    //                 ->seconds(false)
    //                 ->minutesStep(30)
    //                 ->default(now()->setHour(9)->setMinute(0))
    //                 ->required()
    //                 ->suffixIcon('tabler-clock-play')
    //                 ->columnSpan(4),
    //             DateTimePicker::make('finished_at')
    //                 ->label(__('finishedAt'))
    //                 ->weekStartsOnMonday()
    //                 ->seconds(false)
    //                 ->minutesStep(30)
    //                 ->default(now()->setHour(17)->setMinute(0))
    //                 ->required()
    //                 ->suffixIcon('tabler-clock-pause')
    //                 ->columnSpan(4),
    //             TextInput::make('pause_duration')
    //                 ->label(__('pauseDuration'))
    //                 ->numeric()
    //                 ->step(.01)
    //                 ->minValue(0)
    //                 ->default(0)
    //                 ->suffix('h')
    //                 ->suffixIcon('tabler-coffee')
    //                 ->columnSpan(3),
    //             Toggle::make('remote')
    //                 ->label(__('remote'))
    //                 ->inline(false)
    //                 ->default(true)
    //                 ->columnSpan(1),
    //             Textarea::make('description')
    //                 ->label(__('description'))
    //                 ->autosize()
    //                 ->maxLength(65535)
    //                 ->required()
    //                 ->columnSpan(12),
    //         ]);
    // }

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
                    ->tooltip(fn (Invoice $record): string => $record->description),
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
                    ->description(fn (Invoice $record): string => format_money($record->vat, 'eur') . ' ' . __('vat')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\Action::make('create')
                    ->icon('tabler-plus')
                    ->label(__('create'))
                    ->url(fn (): string => '/invoices/create'),
            ])
            ->actions([
                Actions\Action::make('edit')
                    ->icon('tabler-edit')
                    ->label('')
                    ->url(fn (Invoice $obj): string => "/invoices/$obj->id/edit/"),
                Actions\ReplicateAction::make()->icon('tabler-copy')->label(''),
                Actions\DeleteAction::make()->icon('tabler-trash')->label(''),
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
                    ->url(fn (): string => '/invoices/create'),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->paginated(false);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('position', 1);
    }
}
