<?php

namespace App\Filament\Relations;

use App\Models\Position;
use Carbon\Carbon;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;

class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';

    public function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Components\DateTimePicker::make('started_at')
                    ->label(__('startedAt'))
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(9)->setMinute(0))
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                        $started = Carbon::parse($state);
                        $finished = Carbon::parse($get('finished_at'));
                        // handle start is set after finish or day change
                        if ($started >= $finished || !$started->isSameDay($finished)) {
                            $set('finished_at', $started->toDateTimeString());
                        }
                    })
                    ->required()
                    ->suffixIcon('tabler-clock-play')
                    ->columnSpan(4),
                Components\DateTimePicker::make('finished_at')
                    ->label(__('finishedAt'))
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(17)->setMinute(0))
                    ->after('started_at')
                    ->required()
                    ->suffixIcon('tabler-clock-pause')
                    ->columnSpan(4),
                Components\TextInput::make('pause_duration')
                    ->label(__('pauseDuration'))
                    ->numeric()
                    ->step(.01)
                    ->minValue(0)
                    ->default(0)
                    ->suffix('h')
                    ->suffixIcon('tabler-coffee')
                    ->columnSpan(3),
                Components\Toggle::make('remote')
                    ->label(__('remote'))
                    ->inline(false)
                    ->default(true)
                    ->columnSpan(1),
                Components\Textarea::make('description')
                    ->label(__('description'))
                    ->autosize()
                    ->maxLength(65535)
                    ->required()
                    ->columnSpan(12),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(trans_choice('position', 1))
            ->heading(trans_choice('position', 2))
            ->defaultSort('started_at', 'asc')
            ->columns([
                Columns\TextColumn::make('description')
                    ->label(__('description'))
                    ->copyable()
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                Columns\TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn (Position $record): float => $record->duration)
                    ->weight(FontWeight::ExtraBold)
                    ->description(fn (Position $record): string => $record->time_range),
                Columns\ToggleColumn::make('remote')
                    ->label(__('remote')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->actions([
                Actions\EditAction::make()->icon('tabler-edit')->label(''),
                Actions\ReplicateAction::make()->icon('tabler-copy')->label(''),
                Actions\DeleteAction::make()->icon('tabler-trash')->label(''),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->paginated(false);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('position', 1);
    }
}
