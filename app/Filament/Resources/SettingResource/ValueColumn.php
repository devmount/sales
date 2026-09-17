<?php

namespace App\Filament\Resources\SettingResource;

use App\Models\Setting;
use Closure;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Support\Facades\Storage;

/**
 * Renders as the usual inline-editable text input, except for image settings (logo/signature),
 * where it renders a clickable preview that opens an upload modal instead. Filament has no
 * built-in column that varies its type per record, so this conditionally overrides the relevant
 * TextInputColumn hooks based on the record's `type` (Filament's `visible()` can't do this: it
 * is evaluated once for the whole column, not per row).
 */
class ValueColumn extends TextInputColumn
{
    protected ?Action $imageAction = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabledClick(fn(): bool => ! $this->isImageSetting());
    }

    public function imageAction(Action $action): static
    {
        $this->imageAction = $action;

        return $this;
    }

    public function getAction(): Action|Closure|null
    {
        return $this->imageAction;
    }

    public function toEmbeddedHtml(): string
    {
        if (! $this->isImageSetting()) {
            return parent::toEmbeddedHtml();
        }

        $path = $this->getRecord()?->value;
        // temporaryUrl() is the mechanism Laravel provides for serving a private local-disk file.
        $url = $path ? Storage::disk('local')->temporaryUrl($path, now()->addMinutes(5)) : null;

        $content = $url
            ? '<img src="' . e($url) . '" alt="" style="height:2rem;width:2rem;border-radius:0.375rem;object-fit:cover;" />'
            : '<span style="font-size:0.875rem;color:var(--gray-500,#6b7280);">' . e(__('clickToUpload')) . '</span>';

        return <<<HTML
            <div
                class="fi-input-wrp"
                style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem;margin-inline: calc(var(--spacing) * 3);margin-block: calc(var(--spacing) * 4);"
            >
                    $content
                </div>
            HTML;
    }

    protected function isImageSetting(): bool
    {
        $record = $this->getRecord();

        return $record instanceof Setting && $record->type === 'image';
    }
}
