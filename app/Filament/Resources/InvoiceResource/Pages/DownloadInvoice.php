<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Invoice;
use App\Models\Setting;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\Page;
use XMLWriter;

class DownloadInvoice extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.download-invoice';

    public $record;
    public $settings;
    public $xml;

    public function mount(Invoice $record)
    {
        $this->record = $record;
        $this->settings = Setting::pluck('value', 'field');

        $lang = $this->record?->project?->client?->language ?? 'de';
        $label = trans_choice("invoice", 1, [], $lang);
        $filename = "{$record->current_number}_{$label}_{$this->settings['company']}.xml";

        $songs = [
            'song1.mp3' => 'Track 1 - Track Title',
            'song2.mp3' => 'Track 2 - Track Title',
            'song3.mp3' => 'Track 3 - Track Title',
            'song4.mp3' => 'Track 4 - Track Title',
            'song5.mp3' => 'Track 5 - Track Title',
            'song6.mp3' => 'Track 6 - Track Title',
        ];

        $xml = new XMLWriter();
        $xml->openURI($filename);
        $xml->setIndent(true);
        $xml->setIndentString('    ');
        $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('xml');
                    foreach($songs as $song => $track){
                        $xml->startElement('track');
                            $xml->writeElement('path', $song);
                            $xml->writeElement('title', $track);
                        $xml->endElement();
                    }
            $xml->endElement();
        $xml->endDocument();
        $xml->flush();
        unset($xml);
    }
}
