<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Expense;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-credit-card';
    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expended_at')
                    ->label(__('expendedAt'))
                    ->date('j. F Y')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('gross'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable(),
                IconColumn::make('taxable')
                    ->label(__('taxable'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vat')
                    ->label(__('vat'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Expense $record): float => $record->vat)
                    ->color(fn (string $state): string => $state == 0 ? 'gray' : 'normal')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    ->fontFamily(FontFamily::Mono)
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('category'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('description'))
                    ->searchable(),
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
                SelectFilter::make('category')
                    ->label(__('category'))
                    ->options(ExpenseCategory::options())
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
            ->defaultSort('expended_at', 'desc')
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
            'index' => ListExpenses::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('expense', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('expense', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('expense', 2);
    }

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $fields = [
            DatePicker::make('expended_at')
                ->label(__('expendedAt'))
                ->weekStartsOnMonday()
                ->required()
                ->default(now())
                ->suffixIcon('tabler-calendar-dollar')
                ->columnSpanFull(),
            Select::make('category')
                ->label(__('category'))
                ->options(ExpenseCategory::class)
                ->required()
                ->default(ExpenseCategory::Good)
                ->suffixIcon('tabler-tag')
                ->columnSpanFull(),
            TextInput::make('price')
                ->label(__('price'))
                ->numeric()
                ->step(0.01)
                ->suffixIcon('tabler-currency-euro')
                ->columnSpan($columns / 2)
                ->required(),
            TextInput::make('quantity')
                ->label(__('quantity'))
                ->numeric()
                ->step(1)
                ->minValue(1)
                ->default(1)
                ->suffixIcon('tabler-stack')
                ->columnSpan($columns / 2)
                ->required(),
            Toggle::make('taxable')
                ->label(__('taxable'))
                ->inline(false)
                ->columnSpan($columns / 2)
                ->default(true)
                ->live(),
            TextInput::make('vat_rate')
                ->label(__('vatRate'))
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->maxValue(1)
                ->default(0.19)
                ->suffixIcon('tabler-receipt-tax')
                ->columnSpan($columns / 2)
                ->required()
                ->hidden(fn (Get $get): bool => !$get('taxable')),
            Textarea::make('description')
                ->label(__('description'))
                ->maxLength(65535)
                ->columnSpanFull(),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make()->columns($columns)->schema($fields)];
    }
}
