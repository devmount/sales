<?php

namespace Tests\Feature;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\SettingResource\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_settings_page(): void
    {
        $this->get(SettingResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_settings_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSettings::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_all_settings_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSettings::class)
            ->assertCanSeeTableRecords(Setting::all());
    }

    #[Test]
    public function it_updates_a_setting_value_inline(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSettings::class)
            ->call('updateTableColumnState', 'value', 'name', 'Acme Inc.');

        $this->assertSame('Acme Inc.', Setting::get('name'));
    }
}
