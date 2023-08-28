<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Filament\Resources\PositionResource\RelationManagers;
use App\Models\Client;
use App\Models\Position;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\FontFamily;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;
    protected static ?string $navigationIcon = 'tabler-list-details';
    protected static ?int $navigationSort = 35;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Select::make('invoice_id')
                    ->translateLabel()
                    ->relationship('invoice', 'title')
                    ->native(false)
                    ->searchable()
                    ->suffixIcon('tabler-file-stack')
                    ->required()
                    ->columnSpan(6),
                Toggle::make('remote')
                    ->translateLabel()
                    ->inline(false)
                    ->default(true)
                    ->columnSpan(6),
                DateTimePicker::make('started_at')
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(9)->setMinute(0))
                    ->required()
                    ->columnSpan(3),
                DateTimePicker::make('finished_at')
                    ->translateLabel()
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(17)->setMinute(0))
                    ->required()
                    ->columnSpan(3),
                TextInput::make('pause_duration')
                    ->translateLabel()
                    ->numeric()
                    ->step(.01)
                    ->minValue(0)
                    ->required()
                    ->columnSpan(6),
                Textarea::make('description')
                    ->translateLabel()
                    ->autosize()
                    ->maxLength(65535)
                    ->required()
                    ->columnSpan(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('invoice.project.client.color')
                    ->label('')
                    ->tooltip(fn (Position $record): string => $record->invoice?->project?->client?->name),
                TextColumn::make('description')
                    ->translateLabel()
                    ->searchable()
                    ->tooltip(fn (Position $record): string => $record->invoice?->title)
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                TextColumn::make('amount')
                    ->label(__('Hours'))
                    ->state(fn (Position $record): float => $record->duration)
                    ->description(fn (Position $record): string => $record->time_range),
                ToggleColumn::make('remote'),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->translateLabel()
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePositions::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Core data');
    }

    public static function getNavigationLabel(): string
    {
        return __('Positions');
    }

    public static function getModelLabel(): string
    {
        return __('Position');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Positions');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }
}
