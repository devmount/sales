<?php

namespace App\Services;

use fpdf\Enums\PdfTextAlignment;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\PdfDocument;
use App\Models\Setting;
use App\Enums\DocumentColor as Color;
use Carbon\Carbon;

class PdfTemplate extends PdfDocument
{
    private $lang;

    public function __construct(string $lang = null) {
        $this->lang = $lang ?? config('app.locale');
        parent::__construct();
    }

    /**
     * Page header
     */
    public function header(): void
    {
        // Title bar
        $this->setFillColor(0, 32, 51)->rect(0, 9, 210, 30, PdfRectangleStyle::FILL);
        // Logo
        $this->image(Setting::get('logo'), 12, 13, 22, 22, 'JPEG');
        // Title text
        $title = strtoupper(
            $this->getPage() <= 1 ? trans_choice("invoice", 1, [], $this->lang) : __("deliverables", [], $this->lang)
        );
        $this->setFont('FiraSans-ExtraLight', fontSizeInPoint: 26)
            ->setTextColor(...Color::LIGHT->rgb())
            ->text($this->centerX($title), 27, $title);
        // Contact entries
        $this->setFontSizeInPoint(9)
            ->text($this->rightX(Setting::get('phone'), 8), 19, Setting::get('phone'))
            ->text($this->rightX(Setting::get('email'), 8), 25, Setting::get('email'))
            ->text($this->rightX(Setting::get('website'), 8), 31, Setting::get('website'));
        // Start content
        $this->setXY(10, 45);
    }

    /**
     * Page footer
     */
    public function footer(): void
    {
        // Bottom line
        $this->setDrawColor(...Color::LINE->rgb())
            ->setLineWidth(0.4)
            ->line(10, 277, 202, 277);
        // Signature
        $this->image(Setting::get('signature'), 13, 262, 24, 18, 'PNG');
        // Page number
        $this->setY(-26)
            ->setFontSizeInPoint(9)
            ->setTextColor(...Color::GRAY->rgb())
            ->cell(text: \sprintf('%d/{nb}', $this->getPage()), align: PdfTextAlignment::CENTER);

        // Prepare settings, data and labels
        $conf = Setting::pluck('value', 'field');
        $data = collect(['date' => Carbon::now()->locale($this->lang)->isoFormat('LL')]);
        $label = collect([
            'bank' => __("bank", locale: $this->lang),
            'bic' => __("bic", locale: $this->lang),
            'holder' => __("holder", locale: $this->lang),
            'iban' => __("iban", locale: $this->lang),
            'taxOffice' => __("taxOffice", locale: $this->lang),
            'vatId' => __("vatId", locale: $this->lang),
        ]);

        // Convert to supported char encoding
        $conf = $conf->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));
        $data = $data->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));
        $label = $label->map(fn ($e) => iconv('UTF-8', 'windows-1252', $e));

        // Footer content
        $this->setXY(9, -18)
            ->multiCell(null, 4, "{$conf['name']}\n{$conf['city']}, {$data['date']}")
            ->setXY(50, -18)
            ->multiCell(40, 4, "{$label['iban']}\n{$label['bic']}\n{$label['bank']}", align: PdfTextAlignment::RIGHT)
            ->setXY(130, -18)
            ->multiCell(40, 4, "{$label['vatId']}\n{$label['taxOffice']}", align: PdfTextAlignment::RIGHT)
            ->setFont('FiraSans-Regular')
            ->setXY(90, -18)
            ->multiCell(50, 4, "{$conf['iban']}\n{$conf['bic']}\n{$conf['bank']}")
            ->setXY(170, -18)
            ->multiCell(40, 4, "{$conf['vatId']}\n{$conf['taxOffice']}");

        // Document guides
        $this->setDrawColor(...Color::LINE->rgb())
            ->line(0, 105, 3, 105)
            ->line(0, 148, 5, 148);
        if ($this->getPage() <= 1) {
            $this->setDrawColor(...Color::LINE4->rgb());
        }
        $this->line(0, 210, 3, 210);
    }

    /**
     * Calculate the horizontal start position of a centered text
     *
     * @param  string $text
     * @param  float|null $anchor If set, this will be used as center point instead of page width
     * @return float
     */
    public function centerX(string $text, ?float $anchor = null): float
    {
        return $anchor === null
            ? ($this->width - $this->getStringWidth($text)) / 2.0
            : $anchor - $this->getStringWidth($text) / 2.0;
    }

    /**
     * Calculate the horizontal start position of a right aligned text
     *
     * @param  string $text
     * @param  float  $margin
     * @return float
     */
    public function rightX(string $text, $margin = 0.0): float
    {
        return ($this->width - $this->getStringWidth($text)) - $margin;
    }
}
