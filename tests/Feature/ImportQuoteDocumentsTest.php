<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportQuoteDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    #[Test]
    public function it_imports_a_single_matching_quote_pdf(): void
    {
        $project = Project::factory()->create();
        $this->putFixture($this->filename($project));

        $this->artisan('documents:import-quotes', ['directory' => $this->directory])
            ->assertExitCode(0);

        $document = $project->documents()->sole();
        $this->assertSame($this->filename($project), $document->filename);
        $this->assertSame('application/pdf', $document->mime_type);
        Storage::assertExists($document->path);
    }

    #[Test]
    public function it_reports_a_file_with_no_matching_project_as_unmatched(): void
    {
        $this->putFixture('202603119999_altes-angebot.pdf');

        $this->artisan('documents:import-quotes', ['directory' => $this->directory])
            ->expectsOutputToContain('no project with id 9999')
            ->assertExitCode(0);

        $this->assertSame(0, Document::count());
    }

    #[Test]
    public function it_reports_an_unparseable_filename_as_unrecognized(): void
    {
        $this->putFixture('not-a-valid-quote-filename.pdf');

        $this->artisan('documents:import-quotes', ['directory' => $this->directory])
            ->expectsOutputToContain('unrecognized filename')
            ->assertExitCode(0);

        $this->assertSame(0, Document::count());
    }

    #[Test]
    public function it_skips_a_project_that_already_has_a_document_unless_forced(): void
    {
        $project = Project::factory()->create();
        $existing = Document::factory()->for($project, 'documentable')->create();
        $this->putFixture($this->filename($project));

        $this->artisan('documents:import-quotes', ['directory' => $this->directory])
            ->assertExitCode(0);

        $this->assertSame(1, $project->documents()->count());
        $this->assertTrue($project->documents()->sole()->is($existing));

        $this->artisan('documents:import-quotes', ['directory' => $this->directory, '--force' => true])
            ->assertExitCode(0);

        $this->assertModelMissing($existing);
        $this->assertSame($this->filename($project), $project->documents()->sole()->filename);
    }

    #[Test]
    public function it_treats_two_plain_files_for_the_same_project_as_a_conflict(): void
    {
        $project = Project::factory()->create();
        $this->putFixture($this->filename($project, '2026-03-11'));
        $this->putFixture($this->filename($project, '2026-03-12'));

        $this->artisan('documents:import-quotes', ['directory' => $this->directory])
            ->expectsOutputToContain('conflict')
            ->assertExitCode(0);

        $this->assertSame(0, $project->documents()->count());
    }

    #[Test]
    public function it_leaves_storage_and_database_untouched_on_a_dry_run(): void
    {
        $project = Project::factory()->create();
        $this->putFixture($this->filename($project));

        $this->artisan('documents:import-quotes', ['directory' => $this->directory, '--dry-run' => true])
            ->expectsOutputToContain('dry run, nothing written')
            ->assertExitCode(0);

        $this->assertSame(0, $project->documents()->count());
        $this->assertSame(0, Document::count());
    }

    #[Test]
    public function it_imports_a_plain_and_version_2_pair_with_version_2_as_latest(): void
    {
        $project = Project::factory()->create();
        $plainName = $this->filename($project);
        $version2Name = str_replace('_angebot', '-2_angebot', $plainName);
        $this->putFixture($plainName);
        $this->putFixture($version2Name);

        $this->artisan('documents:import-quotes', ['directory' => $this->directory])
            ->assertExitCode(0);

        $this->assertSame(2, $project->documents()->count());
        $latest = $project->documents()->latest()->first();
        $this->assertSame($version2Name, $latest->filename);
    }

    #[Test]
    public function it_imports_a_lone_version_2_file_as_the_only_document(): void
    {
        $project = Project::factory()->create();
        $version2Name = str_replace('_angebot', '-2_angebot', $this->filename($project));
        $this->putFixture($version2Name);

        $this->artisan('documents:import-quotes', ['directory' => $this->directory])
            ->assertExitCode(0);

        $document = $project->documents()->sole();
        $this->assertSame($version2Name, $document->filename);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->directory = sys_get_temp_dir() . '/quote-import-test-' . uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob("{$this->directory}/*") as $file) {
            unlink($file);
        }
        rmdir($this->directory);

        parent::tearDown();
    }

    private function filename(Project $project, string $date = '2026-03-11'): string
    {
        return str_replace('-', '', $date) . str_pad((string) $project->id, 4, '0', STR_PAD_LEFT) . '_angebot.pdf';
    }

    private function putFixture(string $filename): void
    {
        file_put_contents("{$this->directory}/{$filename}", '%PDF-1.4 fixture content');
    }
}
