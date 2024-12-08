<?php

namespace App\Filament\Resources;

use App\Enums\LanguageCode;
use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Mail\ContactClient;
use App\Models\Client;
use App\Models\Setting;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
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
                Components\Section::make()
                    ->columns(12)
                    ->schema([
                        Components\TextInput::make('name')
                            ->label(__('name'))
                            ->hint(__('client.name.hint'))
                            ->hintIcon('tabler-info-circle')
                            ->columnSpan(6)
                            ->required(),
                        Components\TextInput::make('short')
                            ->label(__('short'))
                            ->columnSpan(3)
                            ->suffixIcon('tabler-letter-spacing'),
                        Components\ColorPicker::make('color')
                            ->label(__('color'))
                            ->columnSpan(3)
                            ->suffixIcon('tabler-palette'),
                        Components\Textarea::make('address')
                            ->label(__('address'))
                            ->columnSpan(6)
                            ->rows(4)
                            ->autosize()
                            ->required(),
                        Components\Select::make('language')
                            ->label(__('language'))
                            ->columnSpan(6)
                            ->suffixIcon('tabler-language')
                            ->options(LanguageCode::class)
                            ->required(),
                        Components\TextInput::make('email')
                            ->label(__('email'))
                            ->columnSpan(6)
                            ->suffixIcon('tabler-mail')
                            ->email(),
                        Components\TextInput::make('phone')
                            ->label(__('phone'))
                            ->columnSpan(6)
                            ->suffixIcon('tabler-phone')
                            ->tel(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\ColorColumn::make('color')
                    ->label(''),
                Columns\TextColumn::make('name')
                    ->label(__('name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Client $record): string => $record->address)
                    ->wrap(),
                Columns\TextColumn::make('language')
                    ->label(__('language'))
                    ->badge()
                    ->sortable(),
                Columns\TextColumn::make('net')
                    ->label(__('net'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Client $record): float => $record->net)
                    ->description(fn (Client $record): string => $record->hours . ' ' . trans_choice('hour', $record->hours)),
                Columns\TextColumn::make('days_to_pay')
                    ->label(__('payment'))
                    ->abbr(__('averagePaymentDuration'), asTooltip: true)
                    ->numeric(1)
                    ->state(fn (Client $record): float => $record->avg_payment_delay)
                    ->description(trans_choice('day', 2)),
                Columns\TextColumn::make('created_at')
                    ->label(__('createdAt'))
                    ->since()
                    ->sortable(),
                Columns\TextColumn::make('updated_at')
                    ->label(__('updatedAt'))
                    ->datetime('j. F Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('language')
                    ->label(__('language'))
                    ->options(LanguageCode::class),
            ])
            ->actions(Actions\ActionGroup::make([
                Actions\EditAction::make()->icon('tabler-edit'),
                Actions\Action::make('kontaktieren')
                    ->disabled(fn (Client $record) => !boolval($record->email))
                    ->icon('tabler-mail')
                    ->form(fn (Client $record) => [
                        Components\TextInput::make('subject')
                            ->label(__('subject'))
                            ->required(),
                        Components\RichEditor::make('content')
                            ->label(__('content'))
                            ->required()
                            ->default(__("email.template.contact.body", [
                                'name' => $record->name,
                                'sender' => Setting::get('name')
                            ])),
                    ])
                    ->action(function (Client $record, array $data) {
                        Mail::to($record->email)->send(
                            (new ContactClient(body: $data['content']))->subject($data['subject'])
                        );
                    }),
                Actions\ReplicateAction::make()->icon('tabler-copy'),
                Actions\DeleteAction::make()->icon('tabler-trash'),
            ]))
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->icon('tabler-trash'),
                ])
                ->icon('tabler-dots-vertical'),
            ])
            ->emptyStateActions([
                Actions\CreateAction::make()->icon('tabler-plus'),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProjectsRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
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
}
