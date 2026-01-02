<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstimateResource\Pages\ListEstimates;
use App\Models\Estimate;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EstimateResource extends Resource
{
    protected static ?string $model = Estimate::class;
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-clock-code';
    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('project.title')
            ->columns([
                ColorColumn::make('project.client.color')
                    ->label('')
                    ->tooltip(fn (Estimate $record): ?string => $record->project?->client?->name),
                TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Estimate $record): string => substr($record->description, 0, 75) . '...'),
                TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weight')
                    ->label(__('weight'))
                    ->numeric()
                    ->sortable(),
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
                SelectFilter::make('project')
                    ->label(trans_choice('project', 1))
                    ->relationship('project', 'title')
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
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstimates::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('estimate', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('estimate', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('estimate', 2);
    }

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $fields = [
            Select::make('project_id')
                ->label(trans_choice('project', 1))
                ->relationship('project', 'title')
                ->searchable()
                ->suffixIcon('tabler-package')
                ->columnSpanFull()
                ->required(),
            TextInput::make('title')
                ->label(__('title'))
                ->columnSpanFull()
                ->required(),
            Textarea::make('description')
                ->label(__('description'))
                ->autosize()
                ->columnSpanFull()
                ->maxLength(65535),
            TextInput::make('amount')
                ->label(__('estimatedHours'))
                ->columnSpan($columns / 2)
                ->numeric()
                ->step(0.1)
                ->minValue(0.1)
                ->suffix('h')
                ->suffixIcon('tabler-clock-exclamation')
                ->required(),
            TextInput::make('weight')
                ->label(__('weight'))
                ->columnSpan($columns / 2)
                ->numeric()
                ->step(1)
                ->helperText(__('definesEstimateSorting'))
                ->suffixIcon('tabler-arrows-sort'),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make()->columns($columns)->schema($fields)];
    }
}
