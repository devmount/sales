<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Invoice;
use App\Models\Setting;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\Page;
use XMLWriter;
use Carbon\Carbon;

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

        $client = $this->record?->project?->client;
        $lang = $client?->language ?? 'de';
        $label = trans_choice("invoice", 1, [], $lang);
        $filename = "{$record->current_number}_{$label}_{$this->settings['company']}.xml";
        $currency = 'EUR';

        $x = new XMLWriter();
        $x->openURI($filename);
        $x->setIndent(true);
        $x->setIndentString('    ');
        $x->startDocument('1.0', 'UTF-8');
            $x->startElement('xml');
            $x->startElement('Invoice');
                $x->writeAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                $x->writeAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                $x->writeAttribute('xmlns:qdt', 'urn:oasis:names:specification:ubl:schema:xsd:QualifiedDataTypes-2');
                $x->writeAttribute('xmlns:udt', 'urn:oasis:names:specification:ubl:schema:xsd:UnqualifiedDataTypes-2');
                $x->writeAttribute('xmlns:ccts', 'urn:un:unece:uncefact:documentation:2');
                $x->writeAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
                $x->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $x->writeAttribute('xsi:schemaLocation', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd');
                // Meta
                $x->writeElement('cbc:CustomizationID', 'urn:cen.eu:en16931:2017');
                $x->writeElement('cbc:ID', $record->current_number);
                $x->writeElement('cbc:IssueDate', Carbon::now()->format('Y-m-d'));
                $x->writeElement('cbc:DueDate', Carbon::now()->addWeeks(2)->format('Y-m-d'));
                $x->writeElement('cbc:DocumentCurrencyCode', $currency);
                // Contractor (me)
                $x->startElement('cac:AccountingSupplierParty');
                    $x->startElement('cac:Party');
                        $x->startElement('cac:PostalAddress');
                            $x->writeElement('cbc:StreetName', $this->settings['street']);
                            $x->writeElement('cbc:CityName', $this->settings['city']);
                            $x->writeElement('cbc:PostalZone', $this->settings['zip']);
                            $x->startElement('cac:Country');
                                $x->writeElement('cbc:IdentificationCode', 'DE'); // TODO: $this->settings['country']
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyTaxScheme');
                            $x->writeElement('cbc:CompanyID', $this->settings['vatId']);
                            $x->startElement('cac:TaxScheme');
                                $x->writeElement('cbc:ID', 'VAT');
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyLegalEntity');
                            $x->writeElement('cbc:RegistrationName', $this->settings['name']);
                            $x->writeElement('cbc:CompanyID', $this->settings['vatId']);
                        $x->endElement();
                        $x->startElement('cac:Contact');
                            $x->writeElement('cbc:ElectronicMail', $this->settings['email']);
                        $x->endElement();
                    $x->endElement();
                $x->endElement();
                // Client
                $x->startElement('cac:AccountingCustomerParty');
                    $x->startElement('cac:Party');
                        $x->startElement('cac:PostalAddress');
                            $x->writeElement('cbc:StreetName', $client?->street);
                            $x->writeElement('cbc:CityName', $client?->city);
                            $x->writeElement('cbc:PostalZone', $client?->zip);
                            $x->startElement('cac:Country');
                                $x->writeElement('cbc:IdentificationCode', $client?->crountry);
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyTaxScheme');
                            $x->writeElement('cbc:CompanyID', $client?->vat_id);
                            $x->startElement('cac:TaxScheme');
                                $x->writeElement('cbc:ID', 'VAT');
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyLegalEntity');
                            $x->writeElement('cbc:RegistrationName', $client?->name);
                            $x->writeElement('cbc:CompanyID', $client?->vat_id);
                        $x->endElement();
                    $x->endElement();
                $x->endElement();
                // Payment
                $x->startElement('cac:PaymentMeans');
                    $x->writeElement('cbc:PaymentID', $record->current_number);
                    $x->startElement('cac:PayeeFinancialAccount');
                        $x->writeElement('cbc:ID', $this->settings['iban']);
                    $x->endElement();
                $x->endElement();
                // Tax
                $x->startElement('cac:TaxTotal');
                    $x->startElement('cbc:TaxAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($record->vat);
                    $x->endElement();
                    $x->startElement('cac:TaxSubtotal');
                        $x->startElement('cbc:TaxableAmount');
                            $x->writeAttribute('currencyID', $currency);
                            $x->text($record->net);
                        $x->endElement();
                        $x->startElement('cbc:TaxAmount');
                            $x->writeAttribute('currencyID', $currency);
                            $x->text($record->vat);
                        $x->endElement();
                        $x->startElement('cac:TaxCategory');
                            $x->writeElement('cbc:ID', 'S');
                            $x->writeElement('cbc:Percent', $record->vat_rate*100);
                            $x->startElement('cac:TaxScheme');
                                $x->writeElement('cbc:ID', 'VAT');
                            $x->endElement();
                        $x->endElement();
                    $x->endElement();
                $x->endElement();
                // Finances
                $x->startElement('cac:LegalMonetaryTotal');
                    $x->startElement('cbc:LineExtensionAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($record->net);
                    $x->endElement();
                    $x->startElement('cbc:TaxExclusiveAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($record->vat);
                    $x->endElement();
                    $x->startElement('cbc:TaxInclusiveAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($record->gross);
                    $x->endElement();
                    $x->startElement('cbc:ChargeTotalAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text(0);
                    $x->endElement();
                    $x->startElement('cbc:PayableAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($record->gross);
                    $x->endElement();
                $x->endElement();
                // Positions
                foreach($record->positions as $key => $position){
                    $x->startElement('cac:InvoiceLine');
                        $x->writeElement('cbc:ID', $key+1);
                        $x->startElement('cbc:InvoicedQuantity');
                            $x->writeAttribute('unitCode', 'EA');
                            $x->text($position->duration);
                        $x->endElement();
                        $x->startElement('cbc:LineExtensionAmount');
                            $x->writeAttribute('currencyID', $currency);
                            $x->text($position->net);
                        $x->endElement();
                        $x->startElement('cac:Item');
                            $x->writeElement('cbc:Description', $position->description);
                        $x->endElement();
                        $x->startElement('cac:Price');
                            $x->startElement('cbc:PriceAmount');
                                $x->writeAttribute('currencyID', $currency);
                                $x->text($position->net);
                            $x->endElement();
                        $x->endElement();
                    $x->endElement();
                }
            $x->endElement();
        $x->endDocument();
        $x->flush();
        unset($x);
    }
}
