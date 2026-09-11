<?php

namespace Tests\Feature;

use App\Enums\PricingUnit;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Models\Client;
use App\Models\Document;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    private string $logoPath;
    private string $signaturePath;

    #[Test]
    public function it_redirects_guests_away_from_the_project_list(): void
    {
        $this->get(ProjectResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_project_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListProjects::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_projects_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $projects = Project::factory()->count(3)->create();

        Livewire::test(ListProjects::class, ['activeTab' => 'all'])
            ->loadTable()
            ->assertCanSeeTableRecords($projects);
    }

    #[Test]
    public function it_creates_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        $data = [
            'client_id' => $client->getKey(),
            'title' => 'New project',
            'description' => 'Project description',
            'start_at' => '2026-01-01',
            'due_at' => '2026-12-31',
            'scope' => 100,
            'price' => 80,
            'pricing_unit' => PricingUnit::Hour->value,
        ];

        Livewire::test(ListProjects::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'client_id' => $client->getKey(),
            'title' => 'New project',
        ]);
    }

    #[Test]
    public function it_requires_a_client_title_start_scope_price_and_pricing_unit_when_creating_a_project(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListProjects::class)
            ->callAction(CreateAction::class, data: [
                'client_id' => '',
                'title' => '',
                'start_at' => '',
                'scope' => '',
                'price' => '',
                'pricing_unit' => '',
            ])
            ->assertHasFormErrors([
                'client_id' => 'required',
                'title' => 'required',
                'start_at' => 'required',
                'scope' => 'required',
                'price' => 'required',
                'pricing_unit' => 'required',
            ]);

        $this->assertDatabaseCount('projects', 0);
    }

    #[Test]
    public function it_updates_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['title' => 'Old title']);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['title' => 'New title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New title', $project->refresh()->title);
    }

    #[Test]
    public function it_requires_a_title_when_updating_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['title' => ''])
            ->call('save')
            ->assertHasFormErrors(['title' => 'required']);
    }

    #[Test]
    public function it_deletes_a_project_from_the_edit_page(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($project);
    }

    #[Test]
    public function it_deletes_a_project_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(ListProjects::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(DeleteAction::class)->table($project));

        $this->assertModelMissing($project);
    }

    #[Test]
    public function it_hides_the_pdf_download_in_the_table_until_a_quote_document_exists(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(ListProjects::class, ['activeTab' => 'all'])
            ->assertTableActionHidden('pdf', $project);

        Document::factory()->for($project, 'documentable')->create(['filename' => 'quote.pdf']);

        Livewire::test(ListProjects::class, ['activeTab' => 'all'])
            ->assertTableActionVisible('pdf', $project);
    }

    #[Test]
    public function it_disables_the_pdf_download_on_the_edit_page_until_a_quote_document_exists(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionDisabled('pdf');

        Document::factory()->for($project, 'documentable')->create(['filename' => 'quote.pdf']);

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionEnabled('pdf');
    }

    #[Test]
    public function it_generates_and_attaches_a_quote_document_from_the_edit_page(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->callAction('generate');

        $this->assertSame(1, $project->documents()->count());
        $this->assertDatabaseHas('documents', [
            'documentable_type' => Project::class,
            'documentable_id' => $project->id,
            'mime_type' => 'application/pdf',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->logoPath = tempnam(sys_get_temp_dir(), 'logo') . '.jpg';
        imagejpeg(imagecreatetruecolor(10, 10), $this->logoPath);

        $this->signaturePath = tempnam(sys_get_temp_dir(), 'signature') . '.png';
        imagepng(imagecreatetruecolor(10, 10), $this->signaturePath);

        $values = [
            'accountHolder' => 'Account Holder',
            'bank' => 'Test Bank',
            'bic' => 'TESTBIC1',
            'city' => 'Berlin',
            'company' => 'Acme UG',
            'country' => 'Germany',
            'email' => 'contact@acme.test',
            'iban' => 'DE00000000000000000000',
            'logo' => $this->logoPath,
            'name' => 'Acme UG',
            'phone' => '+49123456789',
            'signature' => $this->signaturePath,
            'street' => 'Main Street 1',
            'taxOffice' => 'Finanzamt Berlin',
            'vatId' => 'DE123456789',
            'vatRate' => '0.19',
            'website' => 'https://acme.test',
            'zip' => '12345',
        ];

        foreach ($values as $field => $value) {
            Setting::where('field', $field)->update(['value' => $value]);
        }
    }

    protected function tearDown(): void
    {
        @unlink($this->logoPath);
        @unlink($this->signaturePath);

        parent::tearDown();
    }
}
