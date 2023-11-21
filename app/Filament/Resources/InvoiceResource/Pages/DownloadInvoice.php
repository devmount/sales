<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Invoice;
use App\Models\Setting;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\Page;

class DownloadInvoice extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.download-invoice';

    public $record;
    public $settings;

    public function mount(Invoice $record)
    {
        $this->record = $record;
        $this->settings = Setting::pluck('value', 'field');
    }
}
