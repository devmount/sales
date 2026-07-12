<?php

namespace Tests\Feature;

use App\Enums\LanguageCode;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Mail\ContactClient;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_client_list(): void
    {
        $this->get(ClientResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_client_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListClients::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_clients_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $clients = Client::factory()->count(3)->create();

        Livewire::test(ListClients::class)
            ->loadTable()
            ->assertCanSeeTableRecords($clients);
    }

    #[Test]
    public function it_creates_a_client(): void
    {
        $this->actingAs(User::factory()->create());

        $data = [
            'name' => 'Acme Inc.',
            'short' => 'AC',
            'color' => '#3b82f6',
            'address' => 'Suite 5',
            'street' => 'Main Street 1',
            'zip' => '12345',
            'city' => 'Berlin',
            'country' => 'Germany',
            'email' => 'contact@acme.test',
            'phone' => '+49 30 123456',
            'language' => LanguageCode::DE->value,
            'vat_id' => 'DE123456789',
        ];

        Livewire::test(ListClients::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'Acme Inc.',
            'email' => 'contact@acme.test',
            'language' => LanguageCode::DE->value,
        ]);
    }

    #[Test]
    public function it_requires_a_name_and_language_when_creating_a_client(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListClients::class)
            ->callAction(CreateAction::class, data: [
                'name' => '',
                'language' => '',
            ])
            ->assertHasFormErrors(['name' => 'required', 'language' => 'required']);

        $this->assertDatabaseCount('clients', 0);
    }

    #[Test]
    public function it_updates_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create(['name' => 'Old Name']);

        Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New Name', $client->refresh()->name);
    }

    #[Test]
    public function it_requires_a_name_when_updating_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    }

    #[Test]
    public function it_deletes_a_client_from_the_edit_page(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($client);
    }

    #[Test]
    public function it_deletes_a_client_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(ListClients::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($client));

        $this->assertModelMissing($client);
    }

    #[Test]
    public function it_disables_the_contact_action_for_clients_without_an_email(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create(['email' => null]);

        Livewire::test(ListClients::class)
            ->loadTable()
            ->assertActionDisabled(TestAction::make('kontaktieren')->table($client));
    }

    #[Test]
    public function it_enables_the_contact_action_for_clients_with_an_email(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create(['email' => 'client@example.test']);

        Livewire::test(ListClients::class)
            ->loadTable()
            ->assertActionEnabled(TestAction::make('kontaktieren')->table($client));
    }

    #[Test]
    public function it_sends_a_contact_email_to_the_client(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create(['email' => 'client@example.test']);

        Livewire::test(ListClients::class)
            ->callAction(TestAction::make('kontaktieren')->table($client), data: [
                'subject' => 'Hello there',
                'content' => 'This is a test message.',
            ])
            ->assertHasNoFormErrors();

        Mail::assertSent(ContactClient::class, function (ContactClient $mail) use ($client) {
            return $mail->hasTo($client->email)
                && $mail->hasSubject('Hello there')
                && str_contains($mail->body, 'This is a test message.');
        });
    }

    #[Test]
    public function it_requires_a_subject_and_content_to_contact_a_client(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create(['email' => 'client@example.test']);

        Livewire::test(ListClients::class)
            ->callAction(TestAction::make('kontaktieren')->table($client), data: [
                'subject' => '',
                'content' => '',
            ])
            ->assertHasFormErrors(['subject' => 'required', 'content']);

        Mail::assertNothingSent();
    }
}
