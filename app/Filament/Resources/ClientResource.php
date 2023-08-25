<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Mail\ContactClient;
use App\Models\Client;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'tabler-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->translateLabel()
                    ->required(),
                TextInput::make('short')
                    ->translateLabel(),
                ColorPicker::make('color')
                    ->translateLabel(),
                Textarea::make('address')
                    ->translateLabel()
                    ->rows(4)
                    ->autosize()
                    ->required(),
                TextInput::make('email')
                    ->translateLabel()
                    ->email(),
                TextInput::make('phone')
                    ->translateLabel()
                    ->tel(),
                Select::make('language')
                    ->translateLabel()
                    ->options([
                        'de' => 'DE',
                        'en' => 'EN',
                    ])
                    ->native(false)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label(''),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Client $record): string => $record->address)
                    ->wrap(),
                TextColumn::make('language')
                    ->translateLabel()
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->since()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->translateLabel()
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('language')
                    ->translateLabel()
                    ->options([
                        'de' => 'DE',
                        'en' => 'EN',
                    ])
                    ->native(false),
            ])
            ->actions(ActionGroup::make([
                EditAction::make(),
                Action::make('kontaktieren')
                    ->icon('tabler-mail')
                    ->form(fn (Client $record) => [
                        TextInput::make('subject')
                            ->translateLabel()
                            ->required(),
                        RichEditor::make('content')
                            ->translateLabel()
                            ->required()
                            ->default(__("<p>Hi :Name,</p><p> </p><p>Best regards<br>Andreas Müller</p>", ['name' => $record->name])),
                    ])
                    ->action(function (Client $record, array $data) {
                        Mail::to($record->email)->send(
                            (new ContactClient(body: $data['content']))->subject($data['subject'])
                        );
                    })
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Clients');
    }

    public static function getModelLabel(): string
    {
        return __('Client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Clients');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }
}
