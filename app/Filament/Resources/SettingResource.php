<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static ?string $navigationIcon = 'tabler-adjustments';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('weight', 'asc')
            ->reorderable('weight')
            ->columns([
                Columns\TextColumn::make('field')
                    ->label(__('field'))
                    ->state(fn (Setting $record): string => $record->label),
                Columns\TextColumn::make('value')
                    ->label(__('value'))
                    ->limit(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('')
                    ->form(fn (Setting $record) => match ($record->type) {
                        'text', 'email', 'tel', 'url', 'number' => [
                            Components\TextInput::make('value')
                                ->label($record->label)
                                ->email($record->type === 'email')
                                ->tel($record->type === 'tel')
                                ->url($record->type === 'url')
                                ->numeric($record->type === 'number')
                                ->autofocus()
                        ],
                        'textarea' => [
                            Components\Textarea::make('value')
                                ->label($record->label)
                                ->autosize()
                                ->autofocus()
                        ],
                        default => [
                            Components\TextInput::make('value')
                                ->label($record->label)
                                ->autofocus()
                        ]
                    }),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSettings::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('setting', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('setting', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('setting', 2);
    }
}
