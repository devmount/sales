<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportInvoiceDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:import-invoices
        {directory : Folder containing the historical invoice PDFs}
        {--dry-run : Only report matches, without writing anything}
        {--force : Re-import even if the invoice already has a document}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill Document rows for historical invoice PDFs, matched by filename (YYYYMMDDXXXX(-2)_....pdf, XXXX = invoice id). Run with --dry-run first.';

    private int $imported = 0;
    private int $skipped = 0;
    private int $unmatched = 0;
    private int $conflicts = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = rtrim($this->argument('directory'), '/');

        if (!is_dir($directory)) {
            $this->error("Directory not found: {$directory}");
            return self::FAILURE;
        }

        $files = collect(glob("{$directory}/*.pdf"))->sort()->values();

        if ($files->isEmpty()) {
            $this->line('No PDF files found.');
            return self::SUCCESS;
        }

        $groups = $files
            ->map(fn(string $path) => $this->parseFile($path))
            ->groupBy(fn(array $file) => $file['id'] ?? 'unrecognized');

        foreach ($groups as $id => $group) {
            $this->handleGroup($id, $group);
        }

        $this->newLine();
        $summary = "Imported: {$this->imported}, skipped: {$this->skipped}, unmatched: {$this->unmatched}, conflicts: {$this->conflicts}";
        $this->line($this->option('dry-run') ? "{$summary} (dry run, nothing written)" : $summary);

        return self::SUCCESS;
    }

    /**
     * Parse a filename into its embedded invoice id and version marker
     *
     * @return array{path: string, filename: string, id: ?int, isVersion2: bool}
     */
    private function parseFile(string $path): array
    {
        $filename = basename($path);
        $id = null;
        $isVersion2 = false;

        if (preg_match('/^\d{8}(\d{4})(-2)?/', $filename, $matches)) {
            $id = (int) $matches[1];
            $isVersion2 = isset($matches[2]);
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'id' => $id,
            'isVersion2' => $isVersion2,
        ];
    }

    /**
     * Handle all files that parsed to the same invoice id (or "unrecognized")
     *
     * @param  Collection<int, array{path: string, filename: string, id: ?int, isVersion2: bool}>  $group
     */
    private function handleGroup(int|string $id, Collection $group): void
    {
        if ($id === 'unrecognized') {
            foreach ($group as $file) {
                $this->warn("  ✗ {$file['filename']}: unrecognized filename");
            }
            $this->unmatched += $group->count();
            return;
        }

        $ordered = $this->resolveVersionOrder($group);

        if ($ordered === null) {
            foreach ($group as $file) {
                $this->warn("  ✗ {$file['filename']}: conflict, multiple candidate files for invoice #{$id}");
            }
            $this->conflicts++;
            return;
        }

        $invoice = Invoice::find($id);

        if (!$invoice) {
            foreach ($ordered as $file) {
                $this->warn("  ✗ {$file['filename']}: no invoice with id {$id}");
            }
            $this->unmatched++;
            return;
        }

        if ($invoice->documents()->exists() && !$this->option('force')) {
            $this->line("  – invoice #{$id} already has a document, skipping");
            $this->skipped++;
            return;
        }

        if ($this->option('force')) {
            $invoice->documents->each->delete();
        }

        $baseTime = now();

        foreach ($ordered as $file) {
            if ($mismatch = $this->dateMismatch($invoice, $file['filename'])) {
                $this->warn("  ⚠ {$file['filename']}: {$mismatch}");
            }

            $versionLabel = $file['isVersion2'] ? ' (version 2, latest)' : '';

            if ($this->option('dry-run')) {
                $this->line("  ✓ {$file['filename']} → invoice #{$id}{$versionLabel}");
                continue;
            }

            $this->importFile($invoice, $file, $file['isVersion2'] ? $baseTime->copy()->addMinute() : $baseTime);
            $this->line("  ✓ {$file['filename']} → invoice #{$id}{$versionLabel}");
        }

        $this->imported++;
    }

    /**
     * Validate a group of same-id files and return them in import order (plain
     * version first, then version 2), or null if the group is ambiguous
     *
     * @param  Collection<int, array{path: string, filename: string, id: ?int, isVersion2: bool}>  $group
     * @return array<int, array{path: string, filename: string, id: ?int, isVersion2: bool}>|null
     */
    private function resolveVersionOrder(Collection $group): ?array
    {
        if ($group->count() === 1) {
            return $group->values()->all();
        }

        $plain = $group->where('isVersion2', false);
        $version2 = $group->where('isVersion2', true);

        if ($group->count() === 2 && $plain->count() === 1 && $version2->count() === 1) {
            return [...$plain->values(), ...$version2->values()];
        }

        return null;
    }

    /**
     * Warn (non-fatally) when a filename's embedded date doesn't match the invoice's actual invoiced_at
     */
    private function dateMismatch(Invoice $invoice, string $filename): ?string
    {
        if (!$invoice->invoiced_at) {
            return null;
        }

        try {
            $filenameDate = Carbon::createFromFormat('Ymd', substr($filename, 0, 8))->toDateString();
        } catch (\Exception) {
            return null;
        }

        $invoicedDate = Carbon::parse($invoice->invoiced_at)->toDateString();

        if ($filenameDate !== $invoicedDate) {
            return "filename date {$filenameDate} doesn't match invoiced_at ({$invoicedDate})";
        }

        return null;
    }

    /**
     * Copy a source file into permanent per-invoice storage and attach it as a Document
     *
     * @param  array{path: string, filename: string, id: ?int, isVersion2: bool}  $file
     */
    private function importFile(Invoice $invoice, array $file, Carbon $timestamp): void
    {
        $path = "documents/invoices/{$invoice->id}/" . Str::uuid() . '.pdf';
        Storage::disk('local')->put($path, file_get_contents($file['path']));

        $invoice->documents()->create([
            'disk' => 'local',
            'path' => $path,
            'filename' => $file['filename'],
            'mime_type' => 'application/pdf',
            'size' => filesize($file['path']),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
