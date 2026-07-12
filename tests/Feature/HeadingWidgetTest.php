<?php

namespace Tests\Feature;

use App\Livewire\HeadingWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeadingWidgetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_the_given_heading(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(HeadingWidget::class, ['heading' => 'Sales'])
            ->assertSuccessful()
            ->assertSee('Sales');
    }
}
