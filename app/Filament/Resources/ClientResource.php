<?php

namespace App\Filament\Resources;

use App\Enums\LanguageCode;
use App\Filament\Relations\InvoicesRelationManager;
use App\Filament\Relations\ProjectsRelationManager;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Mail\ContactClient;
use App\Models\Client;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-users';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields());
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
                    ->description(fn (Client $record): string => $record->full_address)
                    ->wrap(),
                TextColumn::make('language')
                    ->label(__('language'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('net')
                    ->label(__('net'))
                    ->money('eur')
                    ->fontFamily(FontFamily::Mono)
                    ->state(fn (Client $record): float => $record->net)
                    ->description(fn (Client $record): string => $record->hours . ' ' . trans_choice('hour', $record->hours)),
                TextColumn::make('days_to_pay')
                    ->label(__('payment'))
                    ->abbr(__('averagePaymentDuration'), asTooltip: true)
                    ->numeric(1)
                    ->state(fn (Client $record): float => $record->avg_payment_delay)
                    ->description(trans_choice('day', 2)),
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
                    ->options(LanguageCode::class),
            ])
            ->recordActions(ActionGroup::make([
                EditAction::make()->icon('tabler-edit'),
                Action::make('kontaktieren')
                    ->disabled(fn (Client $record) => !boolval($record->email))
                    ->icon('tabler-mail')
                    ->schema(fn (Client $record) => [
                        TextInput::make('subject')
                            ->label(__('subject'))
                            ->required(),
                        RichEditor::make('content')
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
                    })
                    ->slideOver()
                    ->modalWidth(Width::Large),
                ReplicateAction::make()
                    ->icon('tabler-copy')
                    ->schema(self::formFields(6, false))
                    ->slideOver()
                    ->modalWidth(Width::Large),
                DeleteAction::make()->icon('tabler-trash')->requiresConfirmation(),
            ]))
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
                    ->modalWidth(Width::Large),
            ])
            ->emptyStateIcon('tabler-ban')
            ->defaultSort('created_at', 'desc')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            ProjectsRelationManager::class,
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'edit'  => EditClient::route('/{record}/edit'),
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

    /**
     * Return a list of components containing form fields
     */
    public static function formFields(int $columns = 12, bool $useSection = true): array
    {
        $fields = [
            TextInput::make('name')
                ->label(__('name'))
                ->hint(__('client.name.hint'))
                ->hintIcon('tabler-info-circle')
                ->columnSpan(6)
                ->required(),
            TextInput::make('short')
                ->label(__('short'))
                ->columnSpan(3)
                ->suffixIcon('tabler-letter-spacing'),
            ColorPicker::make('color')
                ->label(__('color'))
                ->columnSpan(3)
                ->suffixIcon('tabler-palette'),
            TextInput::make('address')
                ->label(__('address'))
                ->columnSpan(6),
            Select::make('language')
                ->label(__('language'))
                ->columnSpan(6)
                ->suffixIcon('tabler-language')
                ->options(LanguageCode::class)
                ->required(),
            TextInput::make('street')
                ->label(__('street'))
                ->columnSpan(6),
            TextInput::make('vat_id')
                ->label(__('vatId'))
                ->suffixIcon('tabler-tax-euro')
                ->columnSpan(6),
            TextInput::make('zip')
                ->label(__('zip'))
                ->columnSpan(3),
            TextInput::make('city')
                ->label(__('city'))
                ->columnSpan(3),
            TextInput::make('email')
                ->label(__('email'))
                ->columnSpan(6)
                ->suffixIcon('tabler-mail')
                ->email(),
            TextInput::make('country')
                ->label(__('country'))
                ->columnSpan(6),
            TextInput::make('phone')
                ->label(__('phone'))
                ->columnSpan(6)
                ->suffixIcon('tabler-phone')
                ->tel(),
        ];

        return $useSection
            ? [Section::make()->columnSpan($columns)->schema($fields)->columns($columns)]
            : [Grid::make()->columns($columns)->schema($fields)];
    }
}
