<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftResource\Pages;
use App\Models\Gift;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GiftResource extends Resource
{
    protected static ?string $model = Gift::class;
    protected static ?string $navigationIcon = 'tabler-gift';
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form->schema(self::formFields())->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('received_at')
                    ->label(__('receivedAt'))
                    ->date('j. F Y')
                    ->sortable(),
                TextColumn::make('subject')
                    ->label(__('subject'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Gift $record): string => $record->email ?? ''),
                TextColumn::make('amount')
                    ->label(__('amount'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable()
                    ->summarize(Sum::make()->money('eur')),
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
            ->filters([
                //
            ])
            ->actions(
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('tabler-edit')->slideOver()->modalWidth(MaxWidth::Large),
                    Actions\ReplicateAction::make()->icon('tabler-copy')->form([
                        Components\Grid::make()->columns(2)->schema(self::formFields())
                    ])->slideOver()->modalWidth(MaxWidth::Large),
                    Actions\DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
                ])
                ->icon('tabler-dots-vertical')
            )
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make()->icon('tabler-plus')->slideOver()->modalWidth(MaxWidth::Large),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('received_at', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGifts::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('gift', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('gift', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('gift', 2);
    }

    public static function formFields(): array
    {
        return [
            Components\DatePicker::make('received_at')
                ->label(__('receivedAt'))
                ->weekStartsOnMonday()
                ->suffixIcon('tabler-calendar-heart')
                ->required(),
            Components\TextInput::make('amount')
                ->label(__('amount'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->required(),
            Components\TextInput::make('subject')
                ->label(__('subject'))
                ->columnSpanFull()
                ->suffixIcon('tabler-sticker')
                ->required(),
            Components\TextInput::make('name')
                ->label(__('name'))
                ->suffixIcon('tabler-id'),
            Components\TextInput::make('email')
                ->label(__('email'))
                ->email()
                ->suffixIcon('tabler-mail'),
        ];
    }

}
