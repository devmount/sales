<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages\ManageSettings;
use App\Filament\Resources\SettingResource\ValueColumn;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static string|\BackedEnum|null $navigationIcon = 'tabler-adjustments';

    public static function table(Table $table): Table
    {
        // Attached to the value column below, so clicking an image setting's cell opens the upload modal.
        $upload = Action::make('upload')
            ->schema([
                FileUpload::make('value')
                    ->label(fn(Setting $record): string => $record->label)
                    ->disk('local')
                    ->directory('settings')
                    ->image()
                    ->orientImagesFromExif(false) // Avoid re-encoding
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->imagePreviewHeight('120'),
            ])
            ->fillForm(fn(Setting $record): array => ['value' => $record->value])
            ->action(fn(Setting $record, array $data) => $record->update(['value' => $data['value']]));

        return $table
            ->defaultSort('weight', 'asc')
            ->columns([
                TextColumn::make('field')
                    ->label(__('field'))
                    ->state(fn(Setting $record): string => "{$record->label} (<code>{$record->field}</code>)")
                    ->html()
                    ->tooltip(fn(?Setting $record): ?string => match ($record?->field) {
                        'logo' => __('expectedFormat', ['format' => 'JPEG/PNG']) . '. ' . __('squareRatioRecommended'),
                        'signature' => __('expectedFormat', ['format' => 'JPEG/PNG']),
                        default => null,
                    }),
                ValueColumn::make('value')
                    ->label(__('value'))
                    ->grow()
                    ->imageAction($upload),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSettings::route('/'),
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
