<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Client;
use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                Select::make('client_id')
                    ->translateLabel()
                    ->relationship('client', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->suffixIcon('tabler-users')
                    ->required(),
                Toggle::make('aborted')
                    ->translateLabel()
                    ->inline(false)
                    ->required(),
                TextInput::make('title')
                    ->translateLabel()
                    ->required(),
                Textarea::make('description')
                    ->translateLabel()
                    ->autosize()
                    ->maxLength(65535),
                DatePicker::make('start_at')
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-plus'),
                DatePicker::make('due_at')
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->suffixIcon('tabler-calendar-check'),
                TextInput::make('minimum')
                    ->translateLabel()
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->suffixIcon('tabler-clock-check'),
                TextInput::make('scope')
                    ->translateLabel()
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->suffixIcon('tabler-clock-exclamation'),
                TextInput::make('price')
                    ->translateLabel()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->suffixIcon('tabler-currency-euro')
                    ->required(),
                Select::make('pricing_unit')
                    ->translateLabel()
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
                ColorColumn::make('client.color')
                    ->label(''),
                TextColumn::make('title')
                    ->translateLabel()
                    ->searchable()
                    ->sortable()
                    ->description(fn (Project $record): string => $record->client?->name)
                    ->tooltip(fn (Project $record): string => $record->description),
                TextColumn::make('date_range')
                    ->translateLabel()
                    ->state(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->longAbsoluteDiffForHumans(Carbon::parse($record->due_at), 2)
                    )
                    ->description(fn (Project $record): string => Carbon::parse($record->start_at)
                        ->isoFormat('ll') . ' - ' . Carbon::parse($record->due_at)->isoFormat('ll')
                ),
                TextColumn::make('scope')
                    ->translateLabel()
                    ->state(fn (Project $record): string => $record->minimum != $record->scope
                        ? (int)$record->minimum . ' - ' . (int)$record->scope . ' ' . __('Hours')
                        : (int)$record->scope . ' ' . __('Hours')
                    )
                    ->description(fn (Project $record): string => $record->price . ' €, ' . $record->pricing_unit->getLabel()
                    )
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Core data');
    }

    public static function getNavigationLabel(): string
    {
        return __('Projects');
    }

    public static function getModelLabel(): string
    {
        return __('Project');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Projects');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('due_at');
    }
}
