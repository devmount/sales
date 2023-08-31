<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Filament\Resources\PositionResource\RelationManagers;
use App\Models\Position;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
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
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->label(trans_choice('invoice', 1))
                    ->relationship('invoice', 'title')
                    ->native(false)
                    ->searchable()
                    ->suffixIcon('tabler-file-stack')
                    ->required()
                    ->columnSpan(6),
                Toggle::make('remote')
                    ->label(__('remote'))
                    ->inline(false)
                    ->default(true)
                    ->columnSpan(6),
                DateTimePicker::make('started_at')
                    ->label(__('startedAt'))
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(9)->setMinute(0))
                    ->required()
                    ->columnSpan(3),
                DateTimePicker::make('finished_at')
                    ->label(__('finishedAt'))
                    ->native(false)
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(17)->setMinute(0))
                    ->required()
                    ->columnSpan(3),
                TextInput::make('pause_duration')
                    ->label(__('pauseDuration'))
                    ->numeric()
                    ->step(.01)
                    ->minValue(0)
                    ->required()
                    ->columnSpan(6),
                Textarea::make('description')
                    ->label(__('description'))
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
                    ->label(__('description'))
                    ->searchable()
                    ->tooltip(fn (Position $record): string => $record->invoice?->title)
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn (Position $record): float => $record->duration)
                    ->weight(FontWeight::ExtraBold)
                    ->description(fn (Position $record): string => $record->time_range),
                ToggleColumn::make('remote')
                    ->label(__('remote')),
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
                SelectFilter::make('invoice')
                    ->label(trans_choice('invoice', 1))
                    ->native(false)
                    ->relationship('invoice', 'title', fn (Builder $query) => $query->whereNull('invoiced_at')->whereNull('paid_at')->orderByDesc('created_at'))
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('earliest')
                            ->label(__('earliest'))
                            ->native(false),
                        DatePicker::make('latest')
                            ->label(__('latest'))
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['earliest'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '>=', $date),
                            )
                            ->when(
                                $data['latest'],
                                fn (Builder $query, $date): Builder => $query->whereDate('finished_at', '<=', $date),
                            );
                    }),
                TernaryFilter::make('remote')
                    ->nullable()
                    ->native(false),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
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
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('position', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('position', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('position', 2);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }
}
