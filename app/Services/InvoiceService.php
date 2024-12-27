<?php

namespace App\Services;

use App\Enums\DocumentColor as Color;
use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;
use fpdf\Enums\PdfDestination;
use Illuminate\Support\Facades\Storage;
use XMLWriter;

class InvoiceService
{
    /**
     * Generate invoice PDF, save it and return path/filename
     *
     * @param Invoice $invoice Record to export
     * @return string
     */
    public static function generatePdf(Invoice $invoice): string {
        $settings = Setting::pluck('value', 'field');
        $client = $invoice?->project?->client;
        $lang = $client?->language ?? 'de';

        $label = collect([
            'invoice' => trans_choice("invoice", 1, locale: $lang),
            'invoiceDate' => __("invoiceDate", locale: $lang),
            'invoiceNumber' => __("invoiceNumber", locale: $lang),
            'to' => __("to", locale: $lang),
        ]);

        $data = collect([
            'address' => Setting::address(),
            'clientLocation' => "{$client->zip} {$client->city}",
            'clientName' => strtoupper($client->name),
            'clientStreet' => $client->street,
            'date' => Carbon::now()->locale($lang)->isoFormat('LL'),
            'number' => $invoice->current_number,
        ]);

        // Convert to supported char encoding
        $encoding = 'ISO-8859-1';
        $settings = $settings->map(fn ($e) => mb_convert_encoding($e, $encoding));
        $label = $label->map(fn ($e) => mb_convert_encoding($e, $encoding));
        $data = $data->map(fn ($e) => mb_convert_encoding($e, $encoding));

        // Init document
        $pdf = new PdfTemplate($lang);
        $pdf->addFont('FiraSans-Regular', dir: __DIR__ . '/fonts')
            ->addFont('FiraSans-ExtraLight', dir: __DIR__ . '/fonts')
            ->addFont('FiraSans-ExtraBold', dir: __DIR__ . '/fonts');

        // Cover page
        $pdf->addPage();

        // Address header
        $pdf->setFont('FiraSans-ExtraLight', fontSizeInPoint: 8)
            ->setTextColor(...Color::GRAY->rgb())
            ->text(10, 50, $data['address']);
        $pdf->setFontSizeInPoint(9)
            ->text(10, 62, $label['to']);
        $pdf->setFontSizeInPoint(15)
            ->setTextColor(...Color::MAIN->rgb())
            ->text(10, 69, $data['clientName']);
        $pdf->setDrawColor(...Color::LINE->rgb())
            ->setLineWidth(0.4)
            ->line(0, 73, 70, 73)
            ->line(138, 73, 210, 73);
        $pdf->setFontSizeInPoint(10)
            ->setTextColor(...Color::GRAY->rgb())
            ->text(10, 79, $data['clientStreet'])
            ->text(10, 84, $data['clientLocation'])
            ->text(142, 62.8, $label['invoiceNumber'])
            ->text(142, 68.8, $label['invoiceDate'])
            ->setFont('FiraSans-Regular')
            ->setTextColor(...Color::MAIN->rgb())
            ->text($pdf->rightX($data['number'], 8), 62.8, $data['number'])
            ->text($pdf->rightX($data['date'], 8), 68.8, $data['date']);


        // Save document
        $filename = strtolower("{$data['number']}_{$label['invoice']}_{$settings['company']}.pdf");
        $pdf->output(PdfDestination::FILE, Storage::path($filename));
        return $filename;
    }

    /**
     * Generate invoice XML (EN16931 conform), save it and return path/filename
     *
     * @param Invoice $invoice Record to export
     * @return string
     */
    public static function generateEn16931Xml(Invoice $invoice): string {
        $settings = Setting::pluck('value', 'field');
        $client = $invoice?->project?->client;
        $lang = $client?->language ?? 'de';
        $label = trans_choice("invoice", 1, [], $lang);
        $filename = strtolower("{$invoice->current_number}_{$label}_{$settings['company']}.xml");

        $currency = 'EUR';

        $x = new XMLWriter();
        $x->openURI(Storage::path($filename));
        $x->setIndent(true);
        $x->setIndentString('    ');
        $x->startDocument('1.0', 'UTF-8');
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
                $x->writeElement('cbc:ID', $invoice->current_number);
                $x->writeElement('cbc:IssueDate', Carbon::now()->format('Y-m-d'));
                $x->writeElement('cbc:DueDate', Carbon::now()->addWeeks(2)->format('Y-m-d'));
                // 380 Rechnung
                // 381 Gutschrift
                // 384 Rechnungskorrektur
                $x->writeElement('cbc:InvoiceTypeCode', 380);
                $x->writeElement('cbc:DocumentCurrencyCode', $currency);
                // Contractor (me)
                $x->startElement('cac:AccountingSupplierParty');
                    $x->startElement('cac:Party');
                        $x->startElement('cac:PostalAddress');
                            $x->writeElement('cbc:StreetName', $settings['street']);
                            $x->writeElement('cbc:CityName', $settings['city']);
                            $x->writeElement('cbc:PostalZone', $settings['zip']);
                            $x->startElement('cac:Country');
                                $x->writeElement('cbc:IdentificationCode', 'DE'); // TODO: $settings['country']
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyTaxScheme');
                            $x->writeElement('cbc:CompanyID', $settings['vatId']);
                            $x->startElement('cac:TaxScheme');
                                $x->writeElement('cbc:ID', 'VAT');
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyLegalEntity');
                            $x->writeElement('cbc:RegistrationName', $settings['name']);
                            $x->writeElement('cbc:CompanyID', $settings['vatId']);
                        $x->endElement();
                        $x->startElement('cac:Contact');
                            $x->writeElement('cbc:ElectronicMail', $settings['email']);
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
                                $x->writeElement('cbc:IdentificationCode', $client?->country);
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyTaxScheme');
                            if ($client?->vat_id) {
                                $x->writeElement('cbc:CompanyID', $client->vat_id);
                            }
                            $x->startElement('cac:TaxScheme');
                                $x->writeElement('cbc:ID', 'VAT');
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:PartyLegalEntity');
                            $x->writeElement('cbc:RegistrationName', $client?->name);
                            if ($client?->vat_id) {
                                $x->writeElement('cbc:CompanyID', $client->vat_id);
                            }
                        $x->endElement();
                    $x->endElement();
                $x->endElement();
                // Payment
                $x->startElement('cac:PaymentMeans');
                    // 58 SEPA credit transfer
                    // 59 SEPA direct debit
                    // 57 Standing agreement
                    // 30 Credit transfer (non-SEPA)
                    // 49 Direct debit (non-SEPA)
                    // 48 Bank card
                    $x->writeElement('cbc:PaymentMeansCode', 30);
                    $x->writeElement('cbc:PaymentID', $invoice->current_number);
                    $x->startElement('cac:PayeeFinancialAccount');
                        $x->writeElement('cbc:ID', $settings['iban']);
                    $x->endElement();
                $x->endElement();
                // Tax
                $x->startElement('cac:TaxTotal');
                    $x->startElement('cbc:TaxAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($invoice->vat);
                    $x->endElement();
                    $x->startElement('cac:TaxSubtotal');
                        $x->startElement('cbc:TaxableAmount');
                            $x->writeAttribute('currencyID', $currency);
                            $x->text($invoice->net);
                        $x->endElement();
                        $x->startElement('cbc:TaxAmount');
                            $x->writeAttribute('currencyID', $currency);
                            $x->text($invoice->vat);
                        $x->endElement();
                        $x->startElement('cac:TaxCategory');
                            $x->writeElement('cbc:ID', 'S');
                            $x->writeElement('cbc:Percent', $invoice->vat_rate*100);
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
                        $x->text($invoice->net);
                    $x->endElement();
                    $x->startElement('cbc:TaxExclusiveAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($invoice->net);
                    $x->endElement();
                    $x->startElement('cbc:TaxInclusiveAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($invoice->gross);
                    $x->endElement();
                    $x->startElement('cbc:ChargeTotalAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text(0);
                    $x->endElement();
                    $x->startElement('cbc:PayableAmount');
                        $x->writeAttribute('currencyID', $currency);
                        $x->text($invoice->gross);
                    $x->endElement();
                $x->endElement();
                // Positions
                foreach($invoice->positions as $key => $position){
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
                            $x->writeElement('cbc:Name', trans_choice('position', 1, locale: $lang));
                            $x->startElement('cac:ClassifiedTaxCategory');
                                $x->writeElement('cbc:ID', 'S');
                                $x->writeElement('cbc:Percent', $invoice->vat_rate*100);
                                $x->startElement('cac:TaxScheme');
                                    $x->writeElement('cbc:ID', 'VAT');
                                $x->endElement();
                            $x->endElement();
                        $x->endElement();
                        $x->startElement('cac:Price');
                            $x->startElement('cbc:PriceAmount');
                                $x->writeAttribute('currencyID', $currency);
                                $x->text($invoice->price);
                            $x->endElement();
                        $x->endElement();
                    $x->endElement();
                }
            $x->endElement();
        $x->endDocument();
        $x->flush();
        unset($x);
        return $filename;
    }
}
