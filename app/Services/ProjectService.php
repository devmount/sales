<?php

namespace App\Services;

use App\Enums\DocumentColor as Color;
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
use XMLWriter;

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

        $data = collect([
            'address' => Setting::address(),
            'clientLocation' => "{$client->zip} {$client->city}",
            'clientName' => strtoupper($client->name),
            'clientStreet' => $client->street,
            'date' => Carbon::now()->locale($lang)->isoFormat('LL'),
            'validDate' => Carbon::now()->addWeeks(3)->locale($lang)->isoFormat('LL'),
            'description' => $project->description,
            'gross' => Number::currency($project->estimated_gross, 'EUR', locale: $lang),
            'hours' => Number::format($billedPerProject ? $project->scope ?? 0 : $project->estimated_hours, 1, locale: $lang),
            'net' => Number::currency($project->estimated_net, 'EUR', locale: $lang),
            'price' => Number::currency($billedPerProject ? ($project->scope ? $project->price/$project->scope : 0) : $project->price, 'EUR', locale: $lang),
            'title' => $project->title,
            'vat' => Number::currency($project->estimated_vat, 'EUR', locale: $lang),
            'vatRate' => Number::percentage($conf['vat_rate']*100, 2, locale: $lang) . ' ' . __("vat", locale: $lang),
        ]);

        $label = collect([
            'amountNet' => __("amountNet", locale: $lang),
            'credit' => __("credit", locale: $lang),
            'description' => __("description", locale: $lang),
            'disclaimer' => __("disclaimer", locale: $lang),
            'disclaimerText' => __("disclaimerText", locale: $lang),
            'inHours' => __("inHours", locale: $lang),
            'inquiries' => __("inquiries", locale: $lang),
            'invoicing' => __("invoicing", locale: $lang),
            'invoicingText' => __("invoicingText", locale: $lang),
            'otherClients' => __("otherClients", locale: $lang),
            'perHour' => __("perHour", locale: $lang),
            'position' => trans_choice("position", 1, locale: $lang),
            'price' => __("price", locale: $lang),
            'priceSutitle' => $billedPerProject ? __("flatRate", locale: $lang) : __("inHours", locale: $lang),
            'quantity' => __("quantity", locale: $lang),
            'quantitySubtitle' => $billedPerProject ? __("flatRate", locale: $lang) : __("perHour", locale: $lang),
            'quote' => __("quote", locale: $lang),
            'quote' => __("quote", locale: $lang),
            'referenceUse' => __("referenceUse", locale: $lang),
            'referenceUseText' => __("referenceUseText", locale: $lang),
            'regards' => __("withKindRegards", locale: $lang),
            'servicePeriod' => __("servicePeriod", locale: $lang),
            'servicePeriodText' => __("servicePeriodText", locale: $lang),
            'servicePlace' => __("servicePlace", locale: $lang),
            'servicePlaceText' => __("servicePlaceText", ["city" => $conf["city"]], $lang),
            'statementOfWork' => __("statementOfWork", locale: $lang),
            'sum' => __("sum", locale: $lang),
            'to' => __("to", locale: $lang),
            'total' => __("total", locale: $lang),
            'totalAmount' => __("totalAmount", locale: $lang),
            'totalQuote' => __("totalQuote", locale: $lang),
            'validity' => __("validity", locale: $lang),
            'validityText' => __("validityText", locale: $lang),
            'vat' => __("vat", locale: $lang),
            'vatId' => __("vatId", locale: $lang),
            // 'quantityPositions' => $billedPerProject ? '' : __("quantity", locale: $lang),
            // 'pricePositions' => $billedPerProject ? '' : __("price", locale: $lang),
            // 'totalPositions' => $billedPerProject ? '' : __("total", locale: $lang),
            // 'inHoursPositions' => $billedPerProject ? '' : __("inHours", locale: $lang),
            // 'perHourPositions' => $billedPerProject ? '' : __("perHour", locale: $lang),
        ]);

        // Convert to supported char encoding
        $conf = $conf->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));
        $data = $data->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));
        $label = $label->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));

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
            ->textRightX($data['number'], 62.8, 8)
            ->textRightX($data['date'], 68.8, 8);

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
            ->textCenterX($label['quantity'], 118, 115)
            ->textCenterX($label['price'], 118, 146)
            ->setFont('FiraSans-Regular')
            ->setTextColor(...Color::LIGHT->rgb())
            ->textCenterX($label['total'], 118, 182);
        $pdf->setFontSizeInPoint(8)
            ->setFont('FiraSans-ExtraLight')
            ->setTextColor(...Color::DARK->rgb())
            ->text(15, 124, $label['statementOfWork'])
            ->textCenterX($label['priceSutitle'], 124, 115)
            ->textCenterX($label['quantitySubtitle'], 124, 146)
            ->setTextColor(...Color::LIGHT->rgb())
            ->textCenterX($label['sum'], 124, 182);
        $pdf->setTextColor(...Color::DARK->rgb())
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
            ->setTextColor(...Color::LIGHT->rgb())
            ->textCenterX($data['realNet'], 148, 182);

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
                ->textRightX($label['amountNet'], 177, 50)
                ->textRightX($label['credit'], 185, 50)
                ->textRightX($data['vatRate'], 193, 50)
                ->textRightX($data['realNet'], 177, 16)
                ->textRightX($data['discount'], 185, 16)
                ->textRightX($data['vat'], 193, 16)
                ->setTextColor(...Color::LIGHT->rgb())
                ->setFont('FiraSans-Regular')
                ->setFontSizeInPoint(16)
                ->textRightX($label['totalAmount'], 207, 50)
                ->textRightX($data['gross'], 207, 16);
        } else {
            // Total without discount
            $pdf->line(124, 196, 194, 196)
                ->setTextColor(...Color::TEXT->rgb())
                ->setFont('FiraSans-ExtraLight')
                ->setFontSizeInPoint(13)
                ->textRightX($label['amountNet'], 181, 50)
                ->textRightX($data['vatRate'], 190, 50)
                ->textRightX($data['realNet'], 181, 16)
                ->textRightX($data['vat'], 190, 16)
                ->setTextColor(...Color::LIGHT->rgb())
                ->setFont('FiraSans-Regular')
                ->setFontSizeInPoint(16)
                ->textRightX($label['totalAmount'], 205, 50)
                ->textRightX($data['gross'], 205, 16);
        }

        // Terms
        $pdf->setFontSizeInPoint(10)
            ->setFont('FiraSans-ExtraLight')
            ->setTextColor(...Color::DARK->rgb())
            ->setXY(9, 222)
            ->multiCell(180, 5.25, $label['explanation'], align: PdfTextAlignment::LEFT)
            ->text(10, 245, $label['regards'])
            ->text(10, 250, $conf['name']);

        // Positions
        foreach ($invoice->paginated_positions as $positions) {
            $rowHeight = 3.5;
            $totalHeight = array_reduce(
                $positions,
                fn ($a, $c) => $a + count(explode("\n", $c->description)) + 2, 0
            ) * $rowHeight + 32;

            $pdf->addPage();

            $pdf->setLineWidth(0.8)
                ->setFillColor(...Color::COL3->rgb())
                ->rect(10, 50, 113, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor(...Color::COL2->rgb())
                ->line(10, 78, 123, 78);
            $pdf->setFillColor(... $billedPerProject ? Color::COL3->rgb() : Color::COL2->rgb())
                ->rect(123, 50, 26, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor(... $billedPerProject ? Color::COL2->rgb() : Color::COL1->rgb())
                ->line(123, 78, 149, 78)
                ->setFillColor(... $billedPerProject ? Color::COL3->rgb() : Color::COL1->rgb())
                ->rect(149, 50, 26, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor(... $billedPerProject ? Color::COL2->rgb() : Color::COL4->rgb())
                ->line(149, 78, 176, 78)
                ->setFillColor(... $billedPerProject ? Color::COL3->rgb() : Color::ACCENT->rgb())
                ->rect(176, 50, 26, $totalHeight, PdfRectangleStyle::FILL)
                ->setDrawColor(... $billedPerProject ? Color::COL2->rgb() : Color::LINE2->rgb())
                ->line(176, 78, 202, 78);
            $pdf->setFontSizeInPoint(13)
                ->setFont('FiraSans-ExtraLight')
                ->setTextColor(...Color::DARK->rgb())
                ->text(15, 63, $label['position'])
                ->textCenterX($label['quantityPositions'], 63, 136)
                ->textCenterX($label['pricePositions'], 63, 162)
                ->setFont('FiraSans-Regular')
                ->setTextColor(...Color::LIGHT->rgb())
                ->textCenterX($label['totalPositions'], 63, 189);
            $pdf->setFontSizeInPoint(8)
                ->setFont('FiraSans-ExtraLight')
                ->setTextColor(...Color::DARK->rgb())
                ->text(15, 69, $invoice->undated ? $label['description'] : $label['dateAndDescription'])
                ->textCenterX($label['inHoursPositions'], 69, 136)
                ->textCenterX($label['perHourPositions'], 69, 162)
                ->setTextColor(...Color::LIGHT->rgb())
                ->textCenterX($label['pricePositions'], 69, 189);

            // draw positions
            $linesProcessed = 0;
            foreach ($positions as $i => $position) {
                $posdate = Carbon::parse($position->started_at)->locale($lang)->isoFormat('LL');
                $poshours = $position->duration;
                $lineCount = count(explode("\n", trim($position->description))) + 2;
                $num = $i + 1;

                $posdata = collect([
                    'title' => $invoice->undated ? "{$num}. {$label['position']}" : $posdate,
                    'description' => trim($position->description),
                    'hours' => Number::format($billedPerProject ? '' : $poshours, 1, locale: $lang),
                    'price' => $billedPerProject ? '' : Number::currency($invoice->price, 'EUR', locale: $lang),
                    'total' => $billedPerProject ? '' : Number::currency($invoice->price * $poshours, 'EUR', locale: $lang),
                ]);

                // Convert to supported char encoding
                $posdata = $posdata->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));

                $pdf->setTextColor(...Color::DARK->rgb())
                    ->setFont('FiraSans-Regular')
                    ->setFontSizeInPoint(9)
                    ->text(15, (84 + $rowHeight * $linesProcessed), $posdata['title'])
                    ->setFont('FiraSans-ExtraLight')
                    ->setFontSizeInPoint(8)
                    ->setXY(14, (85.5 + $rowHeight * $linesProcessed))
                    ->multiCell(height: $rowHeight, text: $posdata['description'])
                    ->setFontSizeInPoint(11)
                    ->textCenterX($posdata['hours'], (87 + $rowHeight * $linesProcessed), 136)
                    ->textCenterX($posdata['price'], (87 + $rowHeight * $linesProcessed), 162)
                    ->setTextColor(...Color::LIGHT->rgb())
                    ->textRightX($posdata['total'], (87 + $rowHeight * $linesProcessed), 13);

                $linesProcessed += $lineCount;
            }
        }

        // Save document
        $filename = strtolower("{$data['number']}_{$label['invoice']}_{$conf['company']}.pdf");
        $pdf->output(PdfDestination::FILE, Storage::path($filename));
        return $filename;
    }
}
