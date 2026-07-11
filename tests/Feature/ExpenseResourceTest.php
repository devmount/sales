<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Expense;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_away_from_the_expense_list(): void
    {
        $this->get(ExpenseResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function it_renders_the_expense_list_page_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListExpenses::class)->assertSuccessful();
    }

    #[Test]
    public function it_lists_expenses_in_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $expenses = Expense::factory()->count(3)->create();

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->loadTable()
            ->assertCanSeeTableRecords($expenses);
    }

    #[Test]
    public function it_creates_an_expense(): void
    {
        $this->actingAs(User::factory()->create());

        $data = [
            'expended_at' => '2026-01-15',
            'category' => ExpenseCategory::Good->value,
            'price' => 42.5,
            'quantity' => 2,
            'taxable' => true,
            'vat_rate' => 0.19,
            'description' => 'Office supplies',
        ];

        Livewire::test(ListExpenses::class)
            ->callAction(CreateAction::class, data: $data)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('expenses', [
            'category' => ExpenseCategory::Good->value,
            'price' => 42.5,
            'description' => 'Office supplies',
        ]);
    }

    #[Test]
    public function it_requires_expended_at_category_price_and_quantity_when_creating_an_expense(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListExpenses::class)
            ->callAction(CreateAction::class, data: [
                'expended_at' => '',
                'category' => '',
                'price' => '',
                'quantity' => '',
            ])
            ->assertHasFormErrors([
                'expended_at' => 'required',
                'category' => 'required',
                'price' => 'required',
                'quantity' => 'required',
            ]);

        $this->assertDatabaseCount('expenses', 0);
    }

    #[Test]
    public function it_updates_an_expense(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create([
            'category' => ExpenseCategory::Good,
            'description' => 'Old description',
        ]);

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(EditAction::class)->table($expense), data: ['description' => 'New description'])
            ->assertHasNoFormErrors();

        $this->assertSame('New description', $expense->refresh()->description);
    }

    #[Test]
    public function it_requires_a_price_when_updating_an_expense(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(EditAction::class)->table($expense), data: ['price' => ''])
            ->assertHasFormErrors(['price' => 'required']);
    }

    #[Test]
    public function it_deletes_an_expense_from_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(DeleteAction::class)->table($expense));

        $this->assertModelMissing($expense);
    }
}
