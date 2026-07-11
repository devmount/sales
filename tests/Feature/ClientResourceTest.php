<?php

use App\Enums\LanguageCode;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('redirects guests away from the client list', function () {
    $this->get(ClientResource::getUrl('index'))->assertRedirect();
});

it('renders the client list page for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListClients::class)->assertSuccessful();
});

it('lists clients in the table', function () {
    $this->actingAs(User::factory()->create());
    $clients = Client::factory()->count(3)->create();

    Livewire::test(ListClients::class)
        ->loadTable()
        ->assertCanSeeTableRecords($clients);
});

it('creates a client', function () {
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
});

it('requires a name and language when creating a client', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListClients::class)
        ->callAction(CreateAction::class, data: [
            'name' => '',
            'language' => '',
        ])
        ->assertHasFormErrors(['name' => 'required', 'language' => 'required']);

    $this->assertDatabaseCount('clients', 0);
});

it('updates a client', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create(['name' => 'Old Name']);

    Livewire::test(EditClient::class, ['record' => $client->getKey()])
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($client->refresh()->name)->toBe('New Name');
});

it('requires a name when updating a client', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create();

    Livewire::test(EditClient::class, ['record' => $client->getKey()])
        ->fillForm(['name' => ''])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

it('deletes a client from the edit page', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create();

    Livewire::test(EditClient::class, ['record' => $client->getKey()])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($client);
});

it('deletes a client from the table', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create();

    Livewire::test(ListClients::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($client));

    $this->assertModelMissing($client);
});
