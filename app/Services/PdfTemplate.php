<?php

namespace App\Services;

use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfFontStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfMove;
use fpdf\PdfBorder;
use fpdf\PdfDocument;
use App\Models\Setting;
use App\Enums\DocumentColor as Color;

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
        $title = strtoupper(trans_choice("invoice", 1, [], $this->lang));
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
        // position at 1.5 cm from bottom
        $this->setY(-15);
        // Arial italic 8pt
        // $this->setFont(PdfFontName::ARIAL, PdfFontStyle::ITALIC, 8);
        // page number
        $this->cell(null, 10, \sprintf('Page %d/{nb}', $this->getPage()), PdfBorder::none(), PdfMove::RIGHT, PdfTextAlignment::CENTER);
    }

    /**
     * Calculate the horizontal start position of a centered text
     *
     * @param  string $text
     * @return float
     */
    public function centerX(string $text): float
    {
        return ($this->width - $this->getStringWidth($text)) / 2.0;
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
