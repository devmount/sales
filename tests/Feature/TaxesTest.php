<?php

namespace Tests\Feature;

use App\Filament\Pages\Taxes;
use App\Filament\Widgets\TaxOverview;
use App\Filament\Widgets\TaxReturnFormInput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_taxes_page(): void
    {
        $this->get(Taxes::getUrl())->assertRedirect();
    }

    #[Test]
    public function it_renders_the_taxes_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Taxes::class)->assertSuccessful();
    }

    #[Test]
    public function it_includes_the_tax_overview_and_tax_return_form_input_widgets(): void
    {
        $this->assertSame(
            [TaxOverview::class, TaxReturnFormInput::class],
            (new Taxes())->getWidgets(),
        );
    }
}
