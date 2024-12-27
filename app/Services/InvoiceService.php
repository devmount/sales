<?php

namespace App\Services;

use App\Enums\DocumentColor as Color;
use App\Enums\PricingUnit;
use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;
use fpdf\Enums\PdfDestination;
use fpdf\Enums\PdfLineCap;
use fpdf\Enums\PdfRectangleStyle;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
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
        $billedPerProject = $invoice->pricing_unit === PricingUnit::Project;

        $label = collect([
            'amountNet' => __("amountNet", locale: $lang),
            'bank' => __("bank", locale: $lang),
            'bic' => __("bic", locale: $lang),
            'credit' => __("credit", locale: $lang),
            'dateAndDescription' => __("dateAndDescription", locale: $lang),
            'deliverables' => __("deliverables", locale: $lang),
            'description' => __("description", locale: $lang),
            'explanation' => __("invoice.explanation", locale: $lang),
            'holder' => __("holder", locale: $lang),
            'iban' => __("iban", locale: $lang),
            'inHours' => __("inHours", locale: $lang),
            'invoice' => trans_choice("invoice", 1, locale: $lang),
            'invoiceDate' => __("invoiceDate", locale: $lang),
            'invoiceNumber' => __("invoiceNumber", locale: $lang),
            'page' => __("page", locale: $lang) ,
            'perHour' => __("perHour", locale: $lang),
            'position' => trans_choice("position", 1, locale: $lang),
            'price' => __("price", locale: $lang),
            'priceSutitle' => $billedPerProject ? __("flatRate", locale: $lang) : __("inHours", locale: $lang),
            'quantity' => __("quantity", locale: $lang),
            'quantitySubtitle' => $billedPerProject ? __("flatRate", locale: $lang) : __("perHour", locale: $lang),
            'regards' => __("withKindRegards", locale: $lang),
            'statementOfWork' => __("statementOfWork", locale: $lang),
            'sum' => $billedPerProject ? __("sum", locale: $lang) : __("sumOfAllPositions", locale: $lang),
            'taxOffice' => __("taxOffice", locale: $lang),
            'to' => __("to", locale: $lang),
            'total' => __("total", locale: $lang),
            'totalAmount' => __("totalAmount", locale: $lang),
            'vat' => __("vat", locale: $lang),
            'vatId' => __("vatId", locale: $lang),
        ]);

        $data = collect([
            'address' => Setting::address(),
            'clientLocation' => "{$client->zip} {$client->city}",
            'clientName' => strtoupper($client->name),
            'clientStreet' => $client->street,
            'date' => Carbon::now()->locale($lang)->isoFormat('LL'),
            'description' => $invoice->description,
            'discount' => '-' . Number::currency($invoice->discount ?? 0, 'EUR', locale: $lang),
            'gross' => Number::currency($invoice->gross, 'EUR', locale: $lang),
            'hours' => Number::format($billedPerProject ? 1 : $invoice->hours, 1, locale: $lang),
            'realNet' => Number::currency($invoice->real_net, 'EUR', locale: $lang),
            'number' => $invoice->current_number,
            'price' => Number::currency($invoice->price, 'EUR', locale: $lang),
            'title' => $invoice->title,
            'vat' => Number::currency($invoice->vat, 'EUR', locale: $lang),
            'vatRate' => Number::percentage($invoice->vat_rate*100, 2, locale: $lang) . ' ' . __("vat", locale: $lang),
        ]);

        // Convert to supported char encoding
        $encoding = 'windows-1252';
        $settings = $settings->map(fn ($e) => iconv('UTF-8', $encoding, $e));
        $label = $label->map(fn ($e) => iconv('UTF-8', $encoding, $e));
        $data = $data->map(fn ($e) => iconv('UTF-8', $encoding, $e));

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
            ->setLineCap(PdfLineCap::BUTT)
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

        // Cover table
        $pdf->setLineWidth(0.8)
            ->setFillColor(...Color::COL3->rgb())
            ->rect(10, 105, 90, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(...Color::COL2->rgb())
            ->line(10, 133, 100, 133)
            ->setFillColor(...Color::COL2->rgb())
            ->rect(100, 105, 31, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(...Color::COL1->rgb())
            ->line(100, 133, 131, 133)
            ->setFillColor(...Color::COL1->rgb())
            ->rect(131, 105, 30, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(...Color::COL4->rgb())
            ->line(131, 133, 162, 133)
            ->setFillColor(...Color::ACCENT->rgb())
            ->rect(162, 105, 40, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(...Color::LINE2->rgb())
            ->line(162, 133, 202, 133);
        $pdf->setFontSizeInPoint(13)
            ->setFont('FiraSans-ExtraLight')
            ->setTextColor(...Color::DARK->rgb())
            ->text(15, 118, $label['description'])
            ->text($pdf->centerX($label['quantity'], 115), 118, $label['quantity'])
            ->text($pdf->centerX($label['price'], 146), 118, $label['price'])
            ->setFont('FiraSans-Regular')
            ->setTextColor(...Color::LIGHT->rgb())
            ->text($pdf->centerX($label['total'], 182), 118, $label['total']);
        $pdf->setFontSizeInPoint(8)
            ->setFont('FiraSans-ExtraLight')
            ->setTextColor(...Color::DARK->rgb())
            ->text(15, 124, $label['statementOfWork'])
            ->text($pdf->centerX($label['priceSutitle'], 115), 124, $label['priceSutitle'])
            ->text($pdf->centerX($label['quantitySubtitle'], 146), 124, $label['quantitySubtitle'])
            ->setTextColor(...Color::LIGHT->rgb())
            ->text($pdf->centerX($label['sum'], 182), 124, $label['sum']);
        $pdf->setTextColor(...Color::DARK->rgb())
            ->setFont('FiraSans-Regular')
            ->setFontSizeInPoint(9)
            ->text(15, 141, $data['title'])
            ->setFont('FiraSans-ExtraLight')
            ->setFontSizeInPoint(8)
            ->setXY(14, 144)
            ->multiCell(height: 4.25, text: $data['description'])
            ->setFontSizeInPoint(16)
            ->text($pdf->centerX($data['hours'], 115), 148, $data['hours'])
            ->text($pdf->centerX($data['price'], 146), 148, $data['price'])
            ->setTextColor(...Color::LIGHT->rgb())
            ->text($pdf->centerX($data['realNet'], 182), 148, $data['realNet']);

        // Table total
        $pdf->setFillColor(...Color::MAIN->rgb())
            ->rect(0, 165, 210, 50, PdfRectangleStyle::FILL)
            ->setDrawColor(...Color::LINE3->rgb())
            ->setLineWidth(0.3);
        if ($invoice->discount) {
            // Total with discount
            $pdf->line(124, 198, 194, 198)
                ->setTextColor(...Color::TEXT->rgb())
                ->setFont('FiraSans-ExtraLight')
                ->setFontSizeInPoint(13)
                ->text($pdf->rightX($label['amountNet'], 50), 177, $label['amountNet'])
                ->text($pdf->rightX($label['credit'], 50), 185, $label['credit'])
                ->text($pdf->rightX($data['vatRate'], 50), 193, $data['vatRate'])
                ->text($pdf->rightX($data['realNet'], 16), 177, $data['realNet'])
                ->text($pdf->rightX($data['discount'], 16), 185, $data['discount'])
                ->text($pdf->rightX($data['vat'], 16), 193, $data['vat'])
                ->setTextColor(...Color::LIGHT->rgb())
                ->setFont('FiraSans-Regular')
                ->setFontSizeInPoint(16)
                ->text($pdf->rightX($label['totalAmount'], 50), 207, $label['totalAmount'])
                ->text($pdf->rightX($data['gross'], 16), 207, $data['gross']);
        } else {
            // Total without discount
            $pdf->line(124, 196, 194, 196)
                ->setTextColor(...Color::TEXT->rgb())
                ->setFont('FiraSans-ExtraLight')
                ->setFontSizeInPoint(13)
                ->text($pdf->rightX($label['amountNet'], 50), 181, $label['amountNet'])
                ->text($pdf->rightX($data['vatRate'], 50), 190, $data['vatRate'])
                ->text($pdf->rightX($data['realNet'], 16), 181, $data['realNet'])
                ->text($pdf->rightX($data['vat'], 16), 190, $data['vat'])
                ->setTextColor(...Color::LIGHT->rgb())
                ->setFont('FiraSans-Regular')
                ->setFontSizeInPoint(16)
                ->text($pdf->rightX($label['totalAmount'], 50), 205, $label['totalAmount'])
                ->text($pdf->rightX($data['gross'], 16), 205, $data['gross']);
        }

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
