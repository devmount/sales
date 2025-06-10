<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Models\Position;
use Carbon\Carbon;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;
    protected static ?string $navigationIcon = 'tabler-list-details';
    protected static ?int $navigationSort = 35;

    public static function form(Form $form): Form
    {
        return $form->schema(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\ColorColumn::make('invoice.project.client.color')
                    ->label('')
                    ->tooltip(fn (Position $record): ?string => $record->invoice?->project?->client?->name),
                Columns\TextColumn::make('description')
                    ->label(__('description'))
                    ->searchable()
                    ->tooltip(fn (Position $record): ?string => $record->invoice?->title)
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                Columns\TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn (Position $record): float => $record->duration)
                    ->weight(FontWeight::ExtraBold)
                    ->description(fn (Position $record): string => $record->time_range),
                Columns\ToggleColumn::make('remote')
                    ->label(__('remote')),
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
                    ->relationship('invoice.project.client', 'name')
                    ->searchable()
                    ->preload(),
                Filters\SelectFilter::make('project')
                    ->label(trans_choice('project', 1))
                    ->relationship('invoice.project', 'title')
                    ->searchable()
                    ->preload(),
                Filters\SelectFilter::make('invoice')
                    ->label(trans_choice('invoice', 1))
                    ->relationship('invoice', 'title', fn (Builder $query) => $query->whereNull('invoiced_at')->whereNull('paid_at')->orderByDesc('created_at'))
                    ->searchable()
                    ->preload(),
                Filters\Filter::make('created_at')
                    ->form([
                        Components\DatePicker::make('earliest')
                            ->label(__('earliest')),
                        Components\DatePicker::make('latest')
                            ->label(__('latest')),
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
                Filters\TernaryFilter::make('remote')
                    ->nullable(),
            ])
            ->filtersFormColumns(3)
            ->actions(
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->icon('tabler-edit')->slideOver()->modalWidth(MaxWidth::TwoExtraLarge),
                    Actions\ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->form(self::formFields())
                        ->slideOver()
                        ->modalWidth(MaxWidth::TwoExtraLarge),
                    Actions\DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
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
                Actions\CreateAction::make()->icon('tabler-plus')->slideOver()->modalWidth(MaxWidth::TwoExtraLarge),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPositions::route('/'),
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

    public static function formFields(): array
    {
        return [
            Components\Grid::make()->columns(2)->schema([
                Components\Select::make('invoice_id')
                    ->label(trans_choice('invoice', 1))
                    ->relationship('invoice', 'title')
                    ->searchable()
                    ->suffixIcon('tabler-file-stack')
                    ->required()
                    ->columnSpanFull(),
                Components\DateTimePicker::make('started_at')
                    ->label(__('startedAt'))
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(9)->setMinute(0))
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                        $previous = Carbon::parse($old);
                        $started = Carbon::parse($state);
                        $finished = Carbon::parse($get('finished_at'));
                        // handle start is set after finish or day change
                        if ($started >= $finished || !$started->isSameDay($finished)) {
                            $set(
                                'finished_at',
                                $started->addMinutes($previous->diffInMinutes($finished))->toDateTimeString()
                            );
                        }
                    })
                    ->required()
                    ->suffixIcon('tabler-clock-play'),
                Components\DateTimePicker::make('finished_at')
                    ->label(__('finishedAt'))
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(17)->setMinute(0))
                    ->after('started_at')
                    ->required()
                    ->suffixIcon('tabler-clock-pause'),
                Components\TextInput::make('pause_duration')
                    ->label(__('pauseDuration'))
                    ->numeric()
                    ->step(.01)
                    ->minValue(0)
                    ->default(0)
                    ->suffix('h')
                    ->suffixIcon('tabler-coffee'),
                Components\Toggle::make('remote')
                    ->label(__('remote'))
                    ->inline(false)
                    ->default(true),
                Components\Textarea::make('description')
                    ->label(__('description'))
                    ->autosize()
                    ->maxLength(65535)
                    ->required()
                    ->columnSpanFull()
                    ->extraInputAttributes(['class' => 'position-limit']),
            ])
        ];
    }

}
