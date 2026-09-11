<?php

use App\Models\Document;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

it('has expected fillable attributes', function () {
    expect((new Document())->getFillable())->toBe([
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'attachment_path',
        'attachment_filename',
        'attachment_mime_type',
        'attachment_size',
    ]);
});

it('casts attributes to their expected types', function () {
    $document = Document::factory()->create(['size' => '1234', 'attachment_size' => '5678']);

    expect($document->size)->toBeInt()
        ->and($document->size)->toBe(1234)
        ->and($document->attachment_size)->toBeInt()
        ->and($document->attachment_size)->toBe(5678);
});

it('derives the extension from the filename', function () {
    $document = Document::factory()->create(['filename' => 'invoice.PDF']);

    expect($document->extension)->toBe('pdf');
});

it('belongs to its documentable model', function () {
    $invoice = Invoice::factory()->create();
    $document = Document::factory()->for($invoice, 'documentable')->create();

    expect($document->documentable)->toBeInstanceOf(Invoice::class)
        ->and($document->documentable->id)->toBe($invoice->id);
});

it('deletes the underlying file when the record is deleted', function () {
    Storage::fake('local');
    $document = Document::factory()->create([
        'disk' => 'local',
        'path' => 'documents/example.pdf',
    ]);
    Storage::disk('local')->put($document->path, 'content');

    $document->delete();

    Storage::disk('local')->assertMissing($document->path);
});

it('also deletes the attachment file when the record is deleted', function () {
    Storage::fake('local');
    $document = Document::factory()->create([
        'disk' => 'local',
        'path' => 'documents/example.pdf',
        'attachment_path' => 'documents/example.xml',
    ]);
    Storage::disk('local')->put($document->path, 'content');
    Storage::disk('local')->put($document->attachment_path, 'content');

    $document->delete();

    Storage::disk('local')->assertMissing($document->path);
    Storage::disk('local')->assertMissing($document->attachment_path);
});
