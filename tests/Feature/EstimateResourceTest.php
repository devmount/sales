<?php

use App\Filament\Resources\EstimateResource;
use App\Filament\Resources\EstimateResource\Pages\ListEstimates;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('redirects guests away from the estimate list', function () {
    $this->get(EstimateResource::getUrl('index'))->assertRedirect();
});

it('renders the estimate list page for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListEstimates::class)->assertSuccessful();
});

it('lists estimates in the table', function () {
    $this->actingAs(User::factory()->create());
    $estimates = Estimate::factory()->count(3)->create();

    Livewire::test(ListEstimates::class)
        ->loadTable()
        ->assertCanSeeTableRecords($estimates);
});

it('creates an estimate', function () {
    $this->actingAs(User::factory()->create());
    $project = Project::factory()->create();

    $data = [
        'project_id' => $project->getKey(),
        'title' => 'New scope of work',
        'description' => 'Some additional description',
        'amount' => 12.5,
        'weight' => 5,
    ];

    Livewire::test(ListEstimates::class)
        ->callAction(CreateAction::class, data: $data)
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('estimates', [
        'project_id' => $project->getKey(),
        'title' => 'New scope of work',
        'amount' => 12.5,
    ]);
});

it('requires a project, title and amount when creating an estimate', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListEstimates::class)
        ->callAction(CreateAction::class, data: [
            'project_id' => '',
            'title' => '',
            'amount' => '',
        ])
        ->assertHasFormErrors(['project_id' => 'required', 'title' => 'required', 'amount' => 'required']);

    $this->assertDatabaseCount('estimates', 0);
});

it('updates an estimate', function () {
    $this->actingAs(User::factory()->create());
    $estimate = Estimate::factory()->create(['title' => 'Old title']);

    Livewire::test(ListEstimates::class)
        ->callAction(TestAction::make(EditAction::class)->table($estimate), data: ['title' => 'New title'])
        ->assertHasNoFormErrors();

    expect($estimate->refresh()->title)->toBe('New title');
});

it('requires a title when updating an estimate', function () {
    $this->actingAs(User::factory()->create());
    $estimate = Estimate::factory()->create();

    Livewire::test(ListEstimates::class)
        ->callAction(TestAction::make(EditAction::class)->table($estimate), data: ['title' => ''])
        ->assertHasFormErrors(['title' => 'required']);
});

it('deletes an estimate from the table', function () {
    $this->actingAs(User::factory()->create());
    $estimate = Estimate::factory()->create();

    Livewire::test(ListEstimates::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($estimate));

    $this->assertModelMissing($estimate);
});
