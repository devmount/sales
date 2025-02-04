<?php

namespace App\Services;

use App\Enums\DocumentColor as Color;
use App\Enums\DocumentType;
use App\Models\Setting;
use Carbon\Carbon;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;
use fpdf\Traits\PdfAttachmentTrait;

class PdfTemplate extends PdfDocument
{
    use PdfAttachmentTrait;

    // Locale
    private $lang;

    // Document type
    private $type;

    // Attachments
    protected array $files = [];
    protected int $nFiles;
    protected bool $openAttachmentPane = false;

    public function __construct(string $lang = null, DocumentType $type = DocumentType::INVOICE)
    {
        // Init
        $this->lang = $lang ?? config('app.locale');
        $this->type = $type;
        parent::__construct();

        // Init fonts
        $this->addFont('FiraSans-Regular', dir: __DIR__ . '/fonts')
            ->addFont('FiraSans-ExtraLight', dir: __DIR__ . '/fonts')
            ->addFont('FiraSans-ExtraBold', dir: __DIR__ . '/fonts');
    }

    /**
     * Page header
     */
    public function header(): void
    {
        // Title bar
        $this->setFillColor(Color::MAIN->pdf())
            ->rect(0, 9, 210, 30, PdfRectangleStyle::FILL);
        // Logo
        $this->image(Setting::get('logo'), 12, 13, 22, 22, 'JPEG');
        // Title text
        $title = strtoupper(
            match ($this->type) {
                DocumentType::INVOICE => $this->getPage() <= 1
                    ? trans_choice("invoice", 1, locale: $this->lang)
                    : __("deliverables", locale: $this->lang),
                DocumentType::QUOTE => $this->getPage() <= 2
                    ? __("quote", locale: $this->lang)
                    : __("costEstimate", locale: $this->lang),
            }
        );
        $this->setFont('FiraSans-ExtraLight', fontSizeInPoint: 26)
            ->setTextColor(Color::LIGHT->pdf())
            ->textCenterX($title, 27);
        // Contact entries
        $this->setFontSizeInPoint(9)
            ->textRightX(Setting::get('phone'), 19, 8)
            ->textRightX(Setting::get('email'), 25, 8)
            ->textRightX(Setting::get('website'), 31, 8);
        // Start content
        $this->setXY(10, 45);
    }

    /**
     * Page footer
     */
    public function footer(): void
    {
        // Bottom line
        $this->setDrawColor(Color::LINE->pdf())
            ->setLineWidth(0.4)
            ->line(10, 277, 202, 277);
        // Signature
        $this->image(Setting::get('signature'), 13, 262, 24, 18, 'PNG');
        // Page number
        $this->setY(-26)
            ->setFontSizeInPoint(9)
            ->setTextColor(Color::GRAY->pdf())
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
        $conf = $conf->map(fn($e) => iconv('UTF-8', 'windows-1252', $e));
        $data = $data->map(fn($e) => iconv('UTF-8', 'windows-1252', $e));
        $label = $label->map(fn($e) => iconv('UTF-8', 'windows-1252', $e));

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
        $this->setDrawColor(Color::LINE->pdf())
            ->line(0, 105, 3, 105)
            ->line(0, 148, 5, 148);
        if ($this->getPage() <= 1) {
            switch ($this->type) {
                case DocumentType::QUOTE:
                    $this->setDrawColor(Color::COL1->pdf());
                    break;
                case DocumentType::INVOICE:
                default:
                    $this->setDrawColor(Color::LINE4->pdf());
                    break;
            }
        }
        $this->line(0, 210, 3, 210);
    }

    /**
     * Calculate the horizontal start position of a centered text
     *
     * @param  string $text
     * @param  float $y
     * @param  float|null $anchor If set, this will be used as center point instead of page width
     * @return static
     */
    public function textCenterX(string $text, float $y, ?float $anchor = null): static
    {
        $x = $anchor === null
            ? ($this->width - $this->getStringWidth($text)) / 2.0
            : $anchor - $this->getStringWidth($text) / 2.0;
        return $this->text($x, $y, $text);
    }

    /**
     * Calculate the horizontal start position of a right aligned text
     *
     * @param  string $text
     * @param  float  $y
     * @param  float  $offset Margin from right border
     * @return static
     */
    public function textRightX(string $text, float $y, float $offset = 0.0): static
    {
        $x = ($this->width - $this->getStringWidth($text)) - $offset;
        return $this->text($x, $y, $text);
    }

}
