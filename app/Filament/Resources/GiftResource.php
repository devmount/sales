<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftResource\Pages\ListGifts;
use App\Models\Gift;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GiftResource extends Resource
{
    protected static ?string $model = Gift::class;
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-gift';
    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields());
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
            ->recordActions(
                ActionGroup::make([
                    EditAction::make()
                        ->icon('tabler-edit')
                        ->schema(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::Large),
                    ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->schema(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::Large),
                    DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
                ])
                ->icon('tabler-dots-vertical')
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->schema(self::formFields(6, false))
                    ->slideOver()
                    ->modalWidth(Width::Large),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('received_at', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGifts::route('/'),
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

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $fields = [
            DatePicker::make('received_at')
                ->label(__('receivedAt'))
                ->weekStartsOnMonday()
                ->suffixIcon('tabler-calendar-heart')
                ->columnSpan($columns / 2)
                ->required(),
            TextInput::make('amount')
                ->label(__('amount'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->columnSpan($columns / 2)
                ->required(),
            TextInput::make('subject')
                ->label(__('subject'))
                ->columnSpanFull()
                ->suffixIcon('tabler-sticker')
                ->required(),
            TextInput::make('name')
                ->label(__('name'))
                ->columnSpan($columns / 2)
                ->suffixIcon('tabler-id'),
            TextInput::make('email')
                ->label(__('email'))
                ->email()
                ->columnSpan($columns / 2)
                ->suffixIcon('tabler-mail'),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make()->columns($columns)->schema($fields)];
    }

}
