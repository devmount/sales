<?php

namespace App\Services;

use App\Enums\DocumentColor as Color;
use App\Enums\DocumentType;
use App\Enums\PricingUnit;
use App\Models\Project;
use App\Models\Setting;
use Carbon\Carbon;
use fpdf\Enums\PdfDestination;
use fpdf\Enums\PdfLineCap;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class ProjectService
{
    /**
     * Generate project PDF, save it and return path/filename
     *
     * @param Project $project Record to export
     * @return string
     */
    public static function generateQuotePdf(Project $project): string {
        $conf = Setting::pluck('value', 'field');
        $client = $project?->client;
        $lang = $client?->language ?? 'de';
        $billedPerProject = $project->pricing_unit === PricingUnit::Project;
        $now = Carbon::now();

        $data = collect([
            'address' => Setting::address(),
            'clientLocation' => "{$client->zip} {$client->city}",
            'clientName' => strtoupper($client->name),
            'clientStreet' => $client->street,
            'date' => $now->locale($lang)->isoFormat('LL'),
            'description' => $project->description,
            'due' => Carbon::parse($project->due_at)->locale($lang)->isoFormat('LL'),
            'gross' => Number::currency($project->estimated_gross, 'EUR', locale: $lang),
            'hours' => Number::format($billedPerProject ? $project->scope ?? 0 : $project->estimated_hours, 1, locale: $lang),
            'net' => Number::currency($project->estimated_net, 'EUR', locale: $lang),
            'number' => $now->format('Ymd'),
            'price' => Number::currency($billedPerProject ? ($project->scope ? $project->price/$project->scope : 0) : $project->price, 'EUR', locale: $lang),
            'start' => Carbon::parse($project->start_at)->locale($lang)->isoFormat('LL'),
            'title' => $project->title,
            'validDate' => $now->addWeeks(3)->locale($lang)->isoFormat('LL'),
            'vat' => Number::currency($project->estimated_vat, 'EUR', locale: $lang),
            'vatRate' => Number::percentage($conf['vatRate']*100, 2, locale: $lang) . ' ' . __("vat", locale: $lang),
        ]);

        $label = collect([
            'amountNet' => __('amountNet', locale: $lang),
            'credit' => __('credit', locale: $lang),
            'description' => __('description', locale: $lang),
            'disclaimer' => __('disclaimer', locale: $lang),
            'disclaimerText' => __('disclaimerText', locale: $lang),
            'inHours' => __('inHours', locale: $lang),
            'inHoursEstimates' => $billedPerProject ? '' : __('inHours', locale: $lang),
            'inquiries' => __('quote.explanation', locale: $lang) . __('inquiries', locale: $lang),
            'invoicing' => __('invoicing', locale: $lang),
            'invoicingText' => __('invoicingText', locale: $lang),
            'otherClients' => __('otherClients', locale: $lang),
            'perHour' => __('perHour', locale: $lang),
            'perHourEstimates' => $billedPerProject ? '' : __('perHour', locale: $lang),
            'position' => trans_choice('position', 1, locale: $lang),
            'price' => __('price', locale: $lang),
            'priceEstimates' => $billedPerProject ? '' : __('price', locale: $lang),
            'priceSubtitle' => $billedPerProject ? __('flatRate', locale: $lang) : __('perHour', locale: $lang),
            'quantity' => __('quantity', locale: $lang),
            'quantityEstimates' => $billedPerProject ? '' : __('quantity', locale: $lang),
            'quantitySubtitle' => $billedPerProject ? __('flatRate', locale: $lang) : __('inHours', locale: $lang),
            'quote' => __('quote', locale: $lang),
            'quote' => __('quote', locale: $lang),
            'referenceUse' => __('referenceUse', locale: $lang),
            'referenceUseText' => __('referenceUseText', locale: $lang),
            'regards' => __('withKindRegards', locale: $lang),
            'servicePeriod' => __('servicePeriod', locale: $lang),
            'servicePeriodText' => __('servicePeriodText', ['from' => $data['start'], 'to' => $data['due']], $lang),
            'servicePlace' => __('servicePlace', locale: $lang),
            'servicePlaceText' => __('servicePlaceText', ['city' => $conf['city']], $lang),
            'statementOfWork' => __('statementOfWork', locale: $lang),
            'sum' => __('sum', locale: $lang),
            'to' => __('to', locale: $lang),
            'total' => __('total', locale: $lang),
            'totalAmount' => __('totalAmount', locale: $lang),
            'totalEstimates' => $billedPerProject ? '' : __('total', locale: $lang),
            'totalQuote' => __('totalQuote', locale: $lang),
            'validity' => __('validity', locale: $lang),
            'validityText' => __('validityText', ['date' => $data['validDate']], $lang),
            'vat' => __('vat', locale: $lang),
            'vatId' => __('vatId', locale: $lang),
        ]);

        // Convert to supported char encoding
        $conf = $conf->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));
        $data = $data->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));
        $label = $label->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));

        // Init document
        $pdf = new PdfTemplate($lang, DocumentType::QUOTE);

        // Cover page
        $pdf->addPage();

        // Address header
        $pdf->setFont('FiraSans-ExtraLight', fontSizeInPoint: 8)
            ->setTextColor(Color::GRAY->pdf())
            ->text(10, 50, $data['address']);
        $pdf->setFontSizeInPoint(9)
            ->text(10, 62, $label['to']);
        $pdf->setFontSizeInPoint(15)
            ->setTextColor(Color::MAIN->pdf())
            ->text(10, 69, $data['clientName']);
        $pdf->setDrawColor(Color::LINE->pdf())
            ->setLineWidth(0.4)
            ->setLineCap(PdfLineCap::BUTT)
            ->line(0, 73, 70, 73)
            ->line(138, 73, 210, 73);
        $pdf->setFontSizeInPoint(10)
            ->setTextColor(Color::GRAY->pdf())
            ->text(10, 79, $data['clientStreet'])
            ->text(10, 84, $data['clientLocation'])
            ->setFont('FiraSans-Regular')
            ->setTextColor(Color::MAIN->pdf())
            ->textRightX($data['date'], 68.8, 8);

        // Cover table
        $pdf->setLineWidth(0.8)
            ->setFillColor(Color::COL3->pdf())
            ->rect(10, 105, 90, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(Color::COL2->pdf())
            ->line(10, 133, 100, 133)
            ->setFillColor(Color::COL2->pdf())
            ->rect(100, 105, 31, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(Color::COL1->pdf())
            ->line(100, 133, 131, 133)
            ->setFillColor(Color::COL1->pdf())
            ->rect(131, 105, 30, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(Color::COL4->pdf())
            ->line(131, 133, 162, 133)
            ->setFillColor(Color::ACCENT->pdf())
            ->rect(162, 105, 40, 56, PdfRectangleStyle::FILL)
            ->setDrawColor(Color::LINE2->pdf())
            ->line(162, 133, 202, 133);
        $pdf->setFontSizeInPoint(13)
            ->setFont('FiraSans-ExtraLight')
            ->setTextColor(Color::DARK->pdf())
            ->text(15, 118, $label['description'])
            ->textCenterX($label['quantity'], 118, 115)
            ->textCenterX($label['price'], 118, 146)
            ->setFont('FiraSans-Regular')
            ->setTextColor(Color::LIGHT->pdf())
            ->textCenterX($label['sum'], 118, 182);
        $pdf->setFontSizeInPoint(8)
            ->setFont('FiraSans-ExtraLight')
            ->setTextColor(Color::DARK->pdf())
            ->text(15, 124, $label['statementOfWork'])
            ->textCenterX($label['quantitySubtitle'], 124, 115)
            ->textCenterX($label['priceSubtitle'], 124, 146)
            ->setTextColor(Color::LIGHT->pdf())
            ->textCenterX($label['totalQuote'], 124, 182);
        $pdf->setTextColor(Color::DARK->pdf())
            ->setFont('FiraSans-Regular')
            ->setFontSizeInPoint(9)
            ->text(15, 141, $data['title'])
            ->setFont('FiraSans-ExtraLight')
            ->setFontSizeInPoint(8)
            ->setXY(14, 144)
            ->multiCell(height: 4.25, text: $data['description'])
            ->setFontSizeInPoint(16)
            ->textCenterX($data['hours'], 148, 115)
            ->textCenterX($data['price'], 148, 146)
            ->setTextColor(Color::LIGHT->pdf())
            ->textCenterX($data['net'], 148, 182);

        // Table total
        $pdf->setFillColor(Color::COL3->pdf())
            ->rect(0, 165, 210, 50, PdfRectangleStyle::FILL)
            ->setDrawColor(Color::COL1->pdf())
            ->setLineWidth(0.3);
        $pdf->line(124, 196, 194, 196)
            ->setTextColor(Color::DARK->pdf())
            ->setFont('FiraSans-ExtraLight')
            ->setFontSizeInPoint(13)
            ->textRightX($label['amountNet'], 181, 50)
            ->textRightX($data['vatRate'], 190, 50)
            ->textRightX($data['net'], 181, 16)
            ->textRightX($data['vat'], 190, 16)
            ->setFont('FiraSans-Regular')
            ->setFontSizeInPoint(16)
            ->textRightX($label['totalAmount'], 205, 50)
            ->textRightX($data['gross'], 205, 16);

        // Terms
        $pdf->setFontSizeInPoint(10)
            ->setXY(9, 224)
            ->multiCell(160, 8, $label['servicePeriod'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-ExtraLight')
            ->setX(9)
            ->multiCell(160, 5.25, $label['servicePeriodText'], align: PdfTextAlignment::LEFT);

        // Terms page
        $pdf->addPage()
            ->setFont('FiraSans-Regular')
            ->multiCell(160, 8, "\n" . $label['invoicing'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-ExtraLight')
            ->multiCell(160, 5.25, $label['invoicingText'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-Regular')
            ->multiCell(160, 8, "\n" . $label['disclaimer'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-ExtraLight')
            ->multiCell(160, 5.25, $label['disclaimerText'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-Regular')
            ->multiCell(160, 8, "\n" . $label['servicePlace'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-ExtraLight')
            ->multiCell(160, 5.25, $label['servicePlaceText'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-Regular')
            ->multiCell(160, 8, "\n" . $label['referenceUse'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-ExtraLight')
            ->multiCell(160, 5.25, $label['referenceUseText'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-Regular')
            ->multiCell(160, 8, "\n" . $label['validity'], align: PdfTextAlignment::LEFT)
            ->setFont('FiraSans-ExtraLight')
            ->multiCell(160, 5.25, $label['validityText'], align: PdfTextAlignment::LEFT)
            ->setXY(9, 227)
            ->multiCell(160, 5.25, $label['inquiries'], align: PdfTextAlignment::LEFT)
            ->text(10, 245, $label['regards'])
            ->text(10, 250, $conf['name']);

        // Estimated positions
        foreach ($project->paginated_estimates as $estimates) {
            $rowHeight = 3.5;
            $totalHeight = array_reduce(
                $estimates,
                fn ($a, $c) => $a + count(explode("\n", $c->description)) + 2, 0
            ) * $rowHeight + 32;

            $pdf->addPage();

            $pdf->setLineWidth(0.8)
                ->setFillColor(Color::COL3->pdf())
                ->rect(10, 50, 113, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor(Color::COL2->pdf())
                ->line(10, 78, 123, 78);
            $pdf->setFillColor($billedPerProject ? Color::COL3->pdf() : Color::COL2->pdf())
                ->rect(123, 50, 26, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor($billedPerProject ? Color::COL2->pdf() : Color::COL1->pdf())
                ->line(123, 78, 149, 78)
                ->setFillColor($billedPerProject ? Color::COL3->pdf() : Color::COL1->pdf())
                ->rect(149, 50, 26, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor($billedPerProject ? Color::COL2->pdf() : Color::COL4->pdf())
                ->line(149, 78, 176, 78)
                ->setFillColor($billedPerProject ? Color::COL3->pdf() : Color::ACCENT->pdf())
                ->rect(176, 50, 26, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor($billedPerProject ? Color::COL2->pdf() : Color::LINE2->pdf())
                ->line(176, 78, 202, 78);
            $pdf->setFontSizeInPoint(13)
                ->setFont('FiraSans-ExtraLight')
                ->setTextColor(Color::DARK->pdf())
                ->text(15, 63, $label['position'])
                ->textCenterX($label['quantityEstimates'], 63, 136)
                ->textCenterX($label['priceEstimates'], 63, 162)
                ->setFont('FiraSans-Regular')
                ->setTextColor(Color::LIGHT->pdf())
                ->textCenterX($label['totalEstimates'], 63, 189);
            $pdf->setFontSizeInPoint(8)
                ->setFont('FiraSans-ExtraLight')
                ->setTextColor(Color::DARK->pdf())
                ->text(15, 69, $label['description'])
                ->textCenterX($label['inHoursEstimates'], 69, 136)
                ->textCenterX($label['perHourEstimates'], 69, 162)
                ->setTextColor(Color::LIGHT->pdf())
                ->textCenterX($label['priceEstimates'], 69, 189);

            // draw positions
            $linesProcessed = 0;
            foreach ($estimates as $i => $estimate) {
                $lineCount = count(explode("\n", trim($estimate->description))) + 2;

                $estData = collect([
                    'description' => trim($estimate->description),
                    'hours' => Number::format($estimate->amount, 1, locale: $lang),
                    'price' => $billedPerProject ? '' : Number::currency($project->price, 'EUR', locale: $lang),
                    'title' => $estimate->title,
                    'total' => $billedPerProject ? '' : Number::currency($project->price * $estimate->amount, 'EUR', locale: $lang),
                ]);

                // Convert to supported char encoding
                $estData = $estData->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));

                $pdf->setTextColor(Color::DARK->pdf())
                    ->setFont('FiraSans-Regular')
                    ->setFontSizeInPoint(9)
                    ->text(15, (84 + $rowHeight * $linesProcessed), $estData['title'])
                    ->setFont('FiraSans-ExtraLight')
                    ->setFontSizeInPoint(8)
                    ->setXY(14, (85.5 + $rowHeight * $linesProcessed))
                    ->multiCell(height: $rowHeight, text: $estData['description'])
                    ->setFontSizeInPoint(11)
                    ->textCenterX($estData['hours'], (87 + $rowHeight * $linesProcessed), 136)
                    ->textCenterX($estData['price'], (87 + $rowHeight * $linesProcessed), 162)
                    ->setTextColor(Color::LIGHT->pdf())
                    ->textRightX($estData['total'], (87 + $rowHeight * $linesProcessed), 13);

                $linesProcessed += $lineCount;
            }
        }

        // Save document
        $filename = strtolower("{$data['number']}_{$label['quote']}_{$conf['company']}.pdf");
        $pdf->output(PdfDestination::FILE, Storage::path($filename));
        return $filename;
    }
}
