<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages\ListPositions;
use App\Models\Position;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-list-details';
    protected static ?int $navigationSort = 35;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('invoice.project.client.color')
                    ->label('')
                    ->tooltip(fn (Position $record): ?string => $record->invoice?->project?->client?->name),
                TextColumn::make('description')
                    ->label(__('description'))
                    ->searchable()
                    ->tooltip(fn (Position $record): ?string => $record->invoice?->title)
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn (Position $record): float => $record->duration)
                    ->fontFamily(FontFamily::Mono)
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
                SelectFilter::make('client')
                    ->label(trans_choice('client', 1))
                    ->relationship('invoice.project.client', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project')
                    ->label(trans_choice('project', 1))
                    ->relationship('invoice.project', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('invoice')
                    ->label(trans_choice('invoice', 1))
                    ->relationship('invoice', 'title', fn (Builder $query) => $query->active()->orderByDesc('created_at'))
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('earliest')
                            ->label(__('earliest')),
                        DatePicker::make('latest')
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
                TernaryFilter::make('remote')
                    ->nullable(),
            ])
            ->filtersFormColumns(3)
            ->recordActions(
                ActionGroup::make([
                    EditAction::make()
                        ->icon('tabler-edit')
                        ->schema(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::TwoExtraLarge),
                    ReplicateAction::make()
                        ->icon('tabler-copy')
                        ->schema(self::formFields(6, false))
                        ->slideOver()
                        ->modalWidth(Width::TwoExtraLarge),
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
                    ->modalWidth(Width::TwoExtraLarge),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPositions::route('/'),
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

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $fields = [
            Select::make('invoice_id')
                ->label(trans_choice('invoice', 1))
                ->relationship('invoice', 'title')
                ->searchable()
                ->suffixIcon('tabler-file-stack')
                ->required()
                ->columnSpanFull(),
            DateTimePicker::make('started_at')
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
                ->columnSpan($columns / 2)
                ->suffixIcon('tabler-clock-play'),
            DateTimePicker::make('finished_at')
                ->label(__('finishedAt'))
                ->weekStartsOnMonday()
                ->seconds(false)
                ->minutesStep(30)
                ->default(now()->setHour(17)->setMinute(0))
                ->after('started_at')
                ->required()
                ->columnSpan($columns / 2)
                ->suffixIcon('tabler-clock-pause'),
            TextInput::make('pause_duration')
                ->label(__('pauseDuration'))
                ->numeric()
                ->step(.01)
                ->minValue(0)
                ->default(0)
                ->columnSpan($columns / 2)
                ->suffix('h')
                ->suffixIcon('tabler-coffee'),
            Toggle::make('remote')
                ->label(__('remote'))
                ->inline(false)
                ->default(true)
                ->columnSpan($columns / 2),
            Textarea::make('description')
                ->label(__('description'))
                ->autosize()
                ->maxLength(65535)
                ->required()
                ->columnSpanFull()
                ->extraInputAttributes(['class' => 'position-limit']),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make()->columns($columns)->schema($fields)];
    }

}
