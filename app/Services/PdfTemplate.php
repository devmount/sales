<?php

namespace App\Services;

use App\Enums\DocumentColor as Color;
use App\Enums\DocumentType;
use App\Models\Setting;
use Carbon\Carbon;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\Internal\PdfAttachment;
use fpdf\PdfDocument;
use fpdf\PdfException;
use fpdf\Traits\PdfAttachmentTrait;

class PdfTemplate extends PdfDocument
{
    use PdfAttachmentTrait;

    // Locale
    private ?string $lang;

    // Document type
    private DocumentType $type;

    public function __construct(?string $lang = null, DocumentType $type = DocumentType::INVOICE)
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
        $this->setFillColor(Color::MAIN->pdfColor())
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
            ->setTextColor(Color::LIGHT->pdfColor())
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
        $this->setDrawColor(Color::LINE->pdfColor())
            ->setLineWidth(0.4)
            ->line(10, 277, 202, 277);
        // Signature
        $this->image(Setting::get('signature'), 13, 262, 24, 18, 'PNG');
        // Page number
        $this->setY(-26)
            ->setFontSizeInPoint(9)
            ->setTextColor(Color::GRAY->pdfColor())
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
        $this->setDrawColor(Color::LINE->pdfColor())
            ->line(0, 105, 3, 105)
            ->line(0, 148, 5, 148);
        if ($this->getPage() <= 1) {
            switch ($this->type) {
                case DocumentType::QUOTE:
                    $this->setDrawColor(Color::COL1->pdfColor());
                    break;
                case DocumentType::INVOICE:
                default:
                    $this->setDrawColor(Color::LINE4->pdfColor());
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
            ? ($this->getPageWidth() - $this->getStringWidth($text)) / 2.0
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
        $x = ($this->getPageWidth() - $this->getStringWidth($text)) - $offset;
        return $this->text($x, $y, $text);
    }

    /**
     * PHP resolves private-method calls made from *within* a trait's own code (e.g.
     * PdfAttachmentTrait::putResources() calling $this->putAttachments()) against the
     * trait's own private method, even if this class declares a same-named override —
     * private methods aren't virtually dispatched. So putCatalog()/putResources() are
     * re-declared here too (verbatim copies of the trait's versions, which have no bug
     * of their own) purely so their internal putAttachments() calls happen from this
     * class's own scope and correctly reach the corrected version below.
     */
    protected function putCatalog(): void
    {
        parent::putCatalog();
        if ([] === $this->attachments) {
            return;
        }
        $this->writer->putf('/Names <</EmbeddedFiles %d 0 R>>', $this->attachmentNumber);
        $array = \array_map(
            static fn (PdfAttachment $attachment): string => $attachment->formatNumber(),
            $this->attachments
        );
        $this->writer->putf('/AF [%s]', \implode(' ', $array));
    }

    protected function putResources(): void
    {
        parent::putResources();
        if ([] !== $this->attachments) {
            $this->putAttachments();
        }
    }

    /**
     * Corrected copy of PdfAttachmentTrait::putAttachments() (fpdf2 v4.3.10, also present
     * on the library's main branch as of this writing).
     *
     * Upstream bug: the final /Names array is built by wrapping each "<index> <object> 0 R"
     * pair as a single text-string literal, e.g. "(000 23 0 R)", instead of the name/value
     * pair a PDF name tree requires: a string "(000)" followed by a *separate* indirect
     * reference "23 0 R". This produces a malformed /Names array that PDF readers (verified
     * with poppler's pdfdetach) reject with "Invalid FileSpec". This override only changes
     * how that final array is assembled; every other line is unchanged from the trait.
     *
     * @throws PdfException if unable to get the file content of an attachment
     */
    private function putAttachments(): void
    {
        foreach ($this->attachments as $attachment) {
            $file = $attachment->file;
            $name = $attachment->name;

            $contents = \file_get_contents($file);
            if (false === $contents) {
                throw PdfException::format('Unable to get content of the file: %s.', $file);
            }
            $size = \strlen($contents);
            $time = \filemtime($file);
            $date = $this->encoder->formatDate(\is_int($time) ? $time : null);

            $this->writer->putNewObj();
            $attachment->number = $this->writer->getObjectNumber();
            $this->writer->put('<<');
            $this->writer->put('/Type /Filespec');
            $this->writer->putf('/F (%s)', $this->encoder->escape($name));
            $this->writer->putf('/UF %s', $this->encoder->textString($name));
            $this->writer->putf('/EF <</F %d 0 R>>', $this->writer->getObjectNumber() + 1);
            if ($attachment->isDescription()) {
                $this->writer->putf('/Desc %s', $this->encoder->textString($attachment->description));
            }
            $this->writer->put('/AFRelationship /Unspecified');
            $this->writer->put('>>');
            $this->writer->putEndObj();

            $this->writer->putNewObj();
            $this->writer->put('<<');
            $this->writer->put('/Type /EmbeddedFile');
            $this->writer->put('/Subtype /application#2Foctet-stream');
            $this->writer->putf('/Length %d', $size);
            $this->writer->putf('/Params <</Size %d /ModDate %s>>', $size, $this->encoder->textString($date));
            $this->writer->put('>>');
            $this->writer->putStream($contents);
            $this->writer->putEndObj();
        }

        $this->writer->putNewObj();
        $this->attachmentNumber = $this->writer->getObjectNumber();
        $names = [];
        /** @var PdfAttachment $attachment */
        foreach (\array_values($this->attachments) as $index => $attachment) {
            $names[] = $this->encoder->textString(\sprintf('%03d', $index));
            $names[] = $attachment->formatNumber();
        }
        $this->writer->put('<<');
        $this->writer->putf('/Names [%s]', \implode(' ', $names));
        $this->writer->put('>>');
        $this->writer->putEndObj();
    }
}
