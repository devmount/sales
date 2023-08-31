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
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ReplicateAction;
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
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('name'))
                    ->required(),
                TextInput::make('short')
                    ->label(__('short')),
                ColorPicker::make('color')
                    ->label(__('color')),
                Textarea::make('address')
                    ->label(__('address'))
                    ->rows(4)
                    ->autosize()
                    ->required(),
                TextInput::make('email')
                    ->label(__('email'))
                    ->email(),
                TextInput::make('phone')
                    ->label(__('phone'))
                    ->tel(),
                Select::make('language')
                    ->label(__('language'))
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
                    ->label(__('name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Client $record): string => $record->address)
                    ->wrap(),
                TextColumn::make('language')
                    ->label(__('language'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->since()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('updatedAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('language')
                    ->label(__('language'))
                    ->options([
                        'de' => 'DE',
                        'en' => 'EN',
                    ])
                    ->native(false),
            ])
            ->actions(ActionGroup::make([
                EditAction::make()->icon('tabler-edit'),
                Action::make('kontaktieren')
                    ->icon('tabler-mail')
                    ->form(fn (Client $record) => [
                        TextInput::make('subject')
                            ->label(__('subject'))
                            ->required(),
                        RichEditor::make('content')
                            ->label(__('content'))
                            ->required()
                            ->default(__("email.template.contact.body", ['name' => $record->name])),
                    ])
                    ->action(function (Client $record, array $data) {
                        Mail::to($record->email)->send(
                            (new ContactClient(body: $data['content']))->subject($data['subject'])
                        );
                    }),
                ReplicateAction::make()->icon('tabler-copy'),
                DeleteAction::make()->icon('tabler-trash'),
            ]))
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

    public static function getNavigationGroup(): ?string
    {
        return __('coreData');
    }

    public static function getNavigationLabel(): string
    {
        return trans_choice('client', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('client', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('client', 2);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }
}
