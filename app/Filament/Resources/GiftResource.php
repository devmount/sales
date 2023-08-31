<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftResource\Pages;
use App\Filament\Resources\GiftResource\RelationManagers;
use App\Models\Gift;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GiftResource extends Resource
{
    protected static ?string $model = Gift::class;
    protected static ?string $navigationIcon = 'tabler-gift';
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('received_at')
                    ->label(__('receivedAt'))
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->required(),
                TextInput::make('subject')
                    ->label(__('subject'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('name')),
                TextInput::make('email')
                    ->label(__('email'))
                    ->email(),
                TextInput::make('amount')
                    ->label(__('amount'))
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffix('EUR')
                    ->required(),
            ]);
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
                    ->summarize(Sum::make()
                    ->money('eur')),
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
                ActionGroup::make([
                    EditAction::make()->icon('tabler-edit'),
                    ReplicateAction::make()->icon('tabler-copy'),
                    DeleteAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical')
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()->icon('tabler-plus'),
            ])
            ->emptyStateIcon('tabler-ban')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGifts::route('/'),
            'create' => Pages\CreateGift::route('/create'),
            'edit' => Pages\EditGift::route('/{record}/edit'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('received_at');
    }
}
