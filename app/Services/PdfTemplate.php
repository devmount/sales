<?php

namespace App\Services;

use fpdf\Enums\PdfTextAlignment;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\PdfDocument;
use App\Models\Setting;
use App\Enums\DocumentColor as Color;
use Carbon\Carbon;
use Exception;

class PdfTemplate extends PdfDocument
{
    // Locale
    private $lang;

    // Attachments
    protected array $files = [];
    protected int $nFiles;
    protected bool $openAttachmentPane = false;

    public function __construct(string $lang = null)
    {
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

    public function attach(string $file, string $name = '', string $desc = '')
    {
        if ($name == '') {
            $p = strrpos($file, '/');
            if ($p === false) {
                $p = strrpos($file, '\\');
            }
            if ($p !== false) {
                $name = substr($file, $p + 1);
            }
            else {
                $name = $file;
            }
        }
        $this->files[] = ['file' => $file, 'name' => $name, 'desc' => $desc];
        return $this;
    }

    public function openAttachmentPane()
    {
        $this->openAttachmentPane = true;
        return $this;
    }

    protected function putFiles()
    {
        foreach ($this->files as $i => &$info) {
            $file = $info['file'];
            $name = $info['name'];
            $desc = $info['desc'];

            $fc = file_get_contents($file);
            if ($fc === false) {
                $this->error('Cannot open file: ' . $file);
            }
            $size = strlen($fc);
            $date = @date('YmdHisO', filemtime($file));
            $md = 'D:' . substr($date, 0, -2) . "'" . substr($date, -2) . "'";;

            $this->putNewObj();
            $info['n'] = $this->objectNumber;
            $this->put('<<');
            $this->put('/Type /Filespec');
            $this->put('/F (' . $this->escape($name) . ')');
            $this->put('/UF ' . $this->textstring($name));
            $this->put('/EF <</F ' . ($this->objectNumber + 1) . ' 0 R>>');
            if ($desc) {
                $this->put('/Desc ' . $this->textstring($desc));
            }
            $this->put('/AFRelationship /Unspecified');
            $this->put('>>');
            $this->put('endobj');

            $this->putNewObj();
            $this->put('<<');
            $this->put('/Type /EmbeddedFile');
            $this->put('/Subtype /application#2Foctet-stream');
            $this->put('/Length ' . $size);
            $this->put('/Params <</Size ' . $size . ' /ModDate ' . $this->textstring($md) . '>>');
            $this->put('>>');
            $this->putstream($fc);
            $this->put('endobj');
        }
        unset($info);

        $this->putNewObj();
        $this->nFiles = $this->objectNumber;
        $a = array();
        foreach ($this->files as $i => $info) {
            $a[] = $this->textstring(sprintf('%03d', $i)) . ' ' . $info['n'] . ' 0 R';
        }
        $this->put('<<');
        $this->put('/Names [' . implode(' ', $a) . ']');
        $this->put('>>');
        $this->put('endobj');
    }

    protected function putResources(): void
    {
        parent::putResources();
        if (!empty($this->files)) {
            $this->putFiles();
        }
    }

    protected function putCatalog(): void
    {
        parent::putCatalog();
        if (!empty($this->files)) {
            $this->put('/Names <</EmbeddedFiles ' . $this->nFiles . ' 0 R>>');
            $a = array();
            foreach ($this->files as $info) {
                $a[] = $info['n'] . ' 0 R';
            }
            $this->put('/AF [' . implode(' ', $a) . ']');
            if ($this->openAttachmentPane) {
                $this->put('/PageMode /UseAttachments');
            }
        }
    }

    protected function error(string $msg)
    {
        // Fatal error
        throw new Exception('FPDF2 error: ' . $msg);
    }
}
