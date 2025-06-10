<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Resources\Resource;
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
            ->columns([
                Columns\TextColumn::make('field')
                    ->label(__('field'))
                    ->state(fn (Setting $record): string => "{$record->label} (<code>{$record->field}</code>)")
                    ->html(),
                Columns\TextInputColumn::make('value')
                    ->label(__('value'))
                    ->grow(),
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
