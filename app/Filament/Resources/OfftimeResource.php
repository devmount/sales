<?php

namespace App\Filament\Resources;

use App\Enums\OfftimeCategory;
use App\Filament\Resources\OfftimeResource\Pages\ListOfftimes;
use App\Models\Offtime;
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
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OfftimeResource extends Resource
{
    protected static ?string $model = Offtime::class;
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-beach';
    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start')
                    ->label(__('startAt'))
                    ->date('j. F Y')
                    ->sortable(),
                TextColumn::make('days')
                    ->label(trans_choice('day', 2))
                    ->state(fn (Offtime $record): string => $record->days_count ?? '')
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('category'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('description'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(OfftimeCategory::options())
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
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('tabler-plus')
                    ->schema(self::formFields(6, false))
                    ->slideOver()
                    ->modalWidth(Width::Large),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('start', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfftimes::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('offtime', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('offtime', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('offtime', 2);
    }

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $fields = [
            DatePicker::make('start')
                ->label(__('startAt'))
                ->weekStartsOnMonday()
                ->suffixIcon('tabler-calendar-dot')
                ->columnSpan($columns / 2)
                ->required(),
            DatePicker::make('end')
                ->label(__('finished'))
                ->weekStartsOnMonday()
                ->columnSpan($columns / 2)
                ->suffixIcon('tabler-calendar-pause'),
            Select::make('category')
                ->label(__('category'))
                ->columnSpanFull()
                ->options(OfftimeCategory::class)
                ->suffixIcon('tabler-category')
                ->required(),
            Textarea::make('description')
                ->label(__('description'))
                ->columnSpanFull(),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make()->columns($columns)->schema($fields)];
    }
}
