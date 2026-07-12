<?php

namespace Tests\Feature;

use App\Filament\Resources\GiftResource;
use App\Filament\Resources\GiftResource\Pages\ListGifts;
use App\Models\Gift;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GiftResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_gift_list(): void
    {
        $this->get(GiftResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_gift_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListGifts::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_gifts_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $gifts = Gift::factory()->count(3)->create();

        Livewire::test(ListGifts::class)
            ->loadTable()
            ->assertCanSeeTableRecords($gifts);
    }

    #[Test]
    public function it_creates_a_gift(): void
    {
        $this->actingAs(User::factory()->create());

        $data = [
            'received_at' => '2026-03-15',
            'amount' => 100.0,
            'subject' => 'Birthday gift',
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
        ];

        Livewire::test(ListGifts::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('gifts', [
            'subject' => 'Birthday gift',
            'amount' => 100.0,
            'name' => 'Jane Doe',
        ]);
    }

    #[Test]
    public function it_requires_received_at_amount_and_subject_when_creating_a_gift(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListGifts::class)
            ->callAction(CreateAction::class, data: [
                'received_at' => '',
                'amount' => '',
                'subject' => '',
            ])
            ->assertHasFormErrors(['received_at' => 'required', 'amount' => 'required', 'subject' => 'required']);

        $this->assertDatabaseCount('gifts', 0);
    }

    #[Test]
    public function it_updates_a_gift(): void
    {
        $this->actingAs(User::factory()->create());
        $gift = Gift::factory()->create(['subject' => 'Old subject']);

        Livewire::test(ListGifts::class)
            ->callAction(TestAction::make(EditAction::class)->table($gift), data: ['subject' => 'New subject'])
            ->assertHasNoFormErrors();

        $this->assertSame('New subject', $gift->refresh()->subject);
    }

    #[Test]
    public function it_requires_a_subject_when_updating_a_gift(): void
    {
        $this->actingAs(User::factory()->create());
        $gift = Gift::factory()->create();

        Livewire::test(ListGifts::class)
            ->callAction(TestAction::make(EditAction::class)->table($gift), data: ['subject' => ''])
            ->assertHasFormErrors(['subject' => 'required']);
    }

    #[Test]
    public function it_deletes_a_gift_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $gift = Gift::factory()->create();

        Livewire::test(ListGifts::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($gift));

        $this->assertModelMissing($gift);
    }
}
