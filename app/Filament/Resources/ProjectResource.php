<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Services\ProjectService;
use Carbon\Carbon;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'tabler-package';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make()
                    ->columns(12)
                    ->schema([
                        Components\Select::make('client_id')
                            ->label(trans_choice('client', 1))
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->suffixIcon('tabler-users')
                            ->columnSpan(6)
                            ->required(),
                        Components\Toggle::make('aborted')
                            ->label(__('aborted'))
                            ->inline(false)
                            ->columnSpan(6),
                        Components\TextInput::make('title')
                            ->label(__('title'))
                            ->columnSpan(6)
                            ->required(),
                        Components\Textarea::make('description')
                            ->label(__('description'))
                            ->autosize()
                            ->maxLength(65535)
                            ->columnSpan(6),
                        Components\DatePicker::make('start_at')
                            ->label(__('startAt'))
                            ->weekStartsOnMonday()
                            ->suffixIcon('tabler-calendar-plus')
                            ->required()
                            ->columnSpan(6),
                        Components\DatePicker::make('due_at')
                            ->label(__('dueAt'))
                            ->weekStartsOnMonday()
                            ->suffixIcon('tabler-calendar-check')
                            ->columnSpan(6),
                        Components\TextInput::make('minimum')
                            ->label(__('minimum'))
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0.1)
                            ->suffix('h')
                            ->suffixIcon('tabler-clock-check')
                            ->columnSpan(6),
                        Components\TextInput::make('scope')
                            ->label(__('scope'))
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0.1)
                            ->suffix('h')
                            ->suffixIcon('tabler-clock-exclamation')
                            ->required()
                            ->columnSpan(6),
                        Components\TextInput::make('price')
                            ->label(__('price'))
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->suffixIcon('tabler-currency-euro')
                            ->columnSpan(6)
                            ->required(),
                        Components\Select::make('pricing_unit')
                            ->label(__('pricingUnit'))
                            ->options(PricingUnit::class)
                            ->suffixIcon('tabler-clock-2')
                            ->columnSpan(6)
                            ->required(),
                ])
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
                    ->tooltip(fn (Project $record): ?string => $record->description),
                Columns\TextColumn::make('date_range')
                    ->label(__('dateRange'))
                    ->state(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->longAbsoluteDiffForHumans(Carbon::parse($record->due_at), 2)
                    )
                    ->description(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->isoFormat('ll') . ' - ' . ($record->due_at ? Carbon::parse($record->due_at)->isoFormat('ll') : '∞')
                    ),
                Columns\TextColumn::make('scope')
                    ->label(__('scope'))
                    ->state(fn (Project $record): string => $record->scope_range)
                    ->description(fn (Project $record): string => $record->price_per_unit),
                Columns\TextColumn::make('progress')
                    ->label(__('progress'))
                    ->state(fn (Project $record): string => $record->hours_with_label)
                    ->description(fn (Project $record): string => $record->progress_percent),
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
                Filters\SelectFilter::make('client')
                    ->label(trans_choice('client', 1))
                    ->relationship('client', 'name'),
            ])
            ->actions(
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('tabler-edit'),
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->beforeFormFilled(function (Actions\ReplicateAction $action, Project $record) {
                            $year = Carbon::parse($record->due_at)->year + 1;
                            $record->start_at = Carbon::create($year)->format('Y-m-d');
                            $record->due_at = Carbon::create($year, 12, 31)->format('Y-m-d');
                        })
                        ->form([
                            Components\TextInput::make('title')
                                ->label(__('title'))
                                ->required()
                                ->columnSpan(2),
                            Components\Textarea::make('description')
                                ->label(__('description'))
                                ->autosize()
                                ->maxLength(65535)
                                ->columnSpan(2),
                            Components\DatePicker::make('start_at')
                                ->label(__('startAt'))
                                ->weekStartsOnMonday()
                                ->suffixIcon('tabler-calendar-plus')
                                ->required()
                                ->columnSpan(1),
                            Components\DatePicker::make('due_at')
                                ->label(__('dueAt'))
                                ->weekStartsOnMonday()
                                ->suffixIcon('tabler-calendar-check')
                                ->columnSpan(1),
                            Components\TextInput::make('minimum')
                                ->label(__('minimum'))
                                ->numeric()
                                ->step(0.1)
                                ->minValue(0.1)
                                ->suffix('h')
                                ->suffixIcon('tabler-clock-check')
                                ->columnSpan(1),
                            Components\TextInput::make('scope')
                                ->label(__('scope'))
                                ->numeric()
                                ->step(0.1)
                                ->minValue(0.1)
                                ->suffix('h')
                                ->suffixIcon('tabler-clock-exclamation')
                                ->required()
                                ->columnSpan(1),
                            Components\TextInput::make('price')
                                ->label(__('price'))
                                ->numeric()
                                ->step(0.01)
                                ->minValue(0.01)
                                ->suffixIcon('tabler-currency-euro')
                                ->columnSpan(1)
                                ->required(),
                            Components\Select::make('pricing_unit')
                                ->label(__('pricingUnit'))
                                ->options(PricingUnit::class)
                                ->suffixIcon('tabler-clock-2')
                                ->columnSpan(1)
                                ->required(),
                        ]),
                        Actions\Action::make('download')
                            ->label(__('quote'))
                            ->icon('tabler-file-type-pdf')
                            ->action(function (Project $record) {
                                $file = ProjectService::generateQuotePdf($record);
                                return response()->download(Storage::path($file));
                            }),
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
            RelationManagers\EstimatesRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
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
