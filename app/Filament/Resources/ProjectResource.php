<?php

namespace App\Filament\Resources;

use App\Enums\PricingUnit;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'tabler-package';

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
                //
            ])
            ->filters([
                //
            ])
            ->actions(ActionGroup::make([
                EditAction::make(),
            ]))
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
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
}
