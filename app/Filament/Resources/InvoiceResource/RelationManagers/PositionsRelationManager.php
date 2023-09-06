<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';

    public function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                DateTimePicker::make('started_at')
                    ->label(__('startedAt'))
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(9)->setMinute(0))
                    ->required()
                    ->suffixIcon('tabler-clock-play')
                    ->columnSpan(4),
                DateTimePicker::make('finished_at')
                    ->label(__('finishedAt'))
                    ->weekStartsOnMonday()
                    ->seconds(false)
                    ->minutesStep(30)
                    ->default(now()->setHour(17)->setMinute(0))
                    ->required()
                    ->suffixIcon('tabler-clock-pause')
                    ->columnSpan(4),
                TextInput::make('pause_duration')
                    ->label(__('pauseDuration'))
                    ->numeric()
                    ->step(.01)
                    ->minValue(0)
                    ->default(0)
                    ->suffix('h')
                    ->suffixIcon('tabler-coffee')
                    ->columnSpan(3),
                Toggle::make('remote')
                    ->label(__('remote'))
                    ->inline(false)
                    ->default(true)
                    ->columnSpan(1),
                Textarea::make('description')
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
            ->defaultSort('started_at', 'asc')
            ->columns([
                TextColumn::make('description')
                    ->label(__('description'))
                    ->copyable()
                    ->formatStateUsing(fn (string $state): string => nl2br($state))
                    ->html(),
                TextColumn::make('amount')
                    ->label(trans_choice('hour', 2))
                    ->state(fn (Position $record): float => $record->duration)
                    ->weight(FontWeight::ExtraBold)
                    ->description(fn (Position $record): string => $record->time_range),
                ToggleColumn::make('remote')
                    ->label(__('remote')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->icon('tabler-plus'),
            ])
            ->actions([
                EditAction::make()->icon('tabler-edit')->label(''),
                ReplicateAction::make()->icon('tabler-copy')->label(''),
                DeleteAction::make()->icon('tabler-trash')->label(''),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                CreateAction::make()->icon('tabler-plus'),
            ])
            ->paginated(false);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('position', 1);
    }
}
