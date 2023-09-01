<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'tabler-package';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Select::make('client_id')
                    ->label(trans_choice('client', 1))
                    ->relationship('client', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->suffixIcon('tabler-users')
                    ->required(),
                Components\Toggle::make('aborted')
                    ->label(__('aborted'))
                    ->inline(false),
                Components\TextInput::make('title')
                    ->label(__('title'))
                    ->required(),
                Components\Textarea::make('description')
                    ->label(__('description'))
                    ->autosize()
                    ->maxLength(65535),
                Components\DatePicker::make('start_at')
                    ->label(__('startAt'))
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-plus'),
                Components\DatePicker::make('due_at')
                    ->label(__('dueAt'))
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-check'),
                Components\TextInput::make('minimum')
                    ->label(__('minimum'))
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->suffixIcon('tabler-clock-check'),
                Components\TextInput::make('scope')
                    ->label(__('scope'))
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->suffixIcon('tabler-clock-exclamation'),
                Components\TextInput::make('price')
                    ->label(__('price'))
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->required(),
                Components\Select::make('pricing_unit')
                    ->label(__('pricingUnit'))
                    ->options(PricingUnit::class)
                    ->native(false)
                    ->suffixIcon('tabler-clock-2')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\ColorColumn::make('client.color')
                    ->label(''),
                Columns\TextColumn::make('title')
                    ->label(__('title'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Project $record): string => $record->client?->name)
                    ->tooltip(fn (Project $record): string => $record->description),
                Columns\TextColumn::make('date_range')
                    ->label(__('dateRange'))
                    ->state(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->longAbsoluteDiffForHumans(Carbon::parse($record->due_at), 2)
                    )
                    ->description(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->isoFormat('ll') . ' - ' . Carbon::parse($record->due_at)->isoFormat('ll')
                ),
                Columns\TextColumn::make('scope')
                    ->label(__('scope'))
                    ->state(fn (Project $record): string => $record->minimum != $record->scope
                        ? (int)$record->minimum . ' - ' . (int)$record->scope . ' ' . trans_choice('hour', 2)
                        : (int)$record->scope . ' ' . trans_choice('hour', (int)$record->scope)
                    )
                    ->description(fn (Project $record): string => $record->price . ' €, ' . $record->pricing_unit->getLabel()),
                Columns\TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('updated_at')
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
                    Actions\EditAction::make()->icon('tabler-edit'),
                    Actions\ReplicateAction::make()->icon('tabler-copy'),
                    Actions\DeleteAction::make()->icon('tabler-trash'),
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
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('due_at', 'desc')
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('project', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('project', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('project', 2);
    }

}
