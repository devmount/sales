<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Document;
use App\Models\Expense;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    public function it_rejects_a_minor_assets_expense_with_a_net_value_exceeding_800_euros(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListExpenses::class)
            ->callAction(CreateAction::class, data: [
                'expended_at' => '2026-01-15',
                'category' => ExpenseCategory::MinorAssets->value,
                'price' => 800.01,
                'quantity' => 1,
                'taxable' => false,
            ])
            ->assertHasFormErrors(['price']);

        $this->assertDatabaseCount('expenses', 0);
    }

    #[Test]
    public function it_accepts_a_minor_assets_expense_with_a_net_value_of_exactly_800_euros(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListExpenses::class)
            ->callAction(CreateAction::class, data: [
                'expended_at' => '2026-01-15',
                'category' => ExpenseCategory::MinorAssets->value,
                'price' => 800,
                'quantity' => 1,
                'taxable' => false,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('expenses', [
            'category' => ExpenseCategory::MinorAssets->value,
            'price' => 800,
        ]);
    }

    #[Test]
    public function it_accepts_a_minor_assets_expense_with_a_net_value_below_800_euros(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListExpenses::class)
            ->callAction(CreateAction::class, data: [
                'expended_at' => '2026-01-15',
                'category' => ExpenseCategory::MinorAssets->value,
                'price' => 799,
                'quantity' => 1,
                'taxable' => false,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('expenses', [
            'category' => ExpenseCategory::MinorAssets->value,
            'price' => 799,
        ]);
    }

    #[Test]
    public function it_allows_a_non_minor_assets_expense_with_a_net_value_of_800_euros_or_more(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListExpenses::class)
            ->callAction(CreateAction::class, data: [
                'expended_at' => '2026-01-15',
                'category' => ExpenseCategory::Good->value,
                'price' => 1000,
                'quantity' => 1,
                'taxable' => false,
            ])
            ->assertHasNoFormErrors();
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

    #[Test]
    public function it_shows_the_original_filename_when_editing_an_expense_with_a_bill(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);
        Document::factory()->for($expense, 'documentable')->create(['filename' => 'receipt.pdf']);

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->mountAction(TestAction::make(EditAction::class)->table($expense))
            ->assertActionDataSet(['bill_original_name' => 'receipt.pdf']);
    }

    #[Test]
    public function it_hides_the_bill_download_in_the_table_until_a_document_exists(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->assertTableActionHidden('bill', $expense);

        Document::factory()->for($expense, 'documentable')->create();

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->assertTableActionVisible('bill', $expense);
    }

    #[Test]
    public function it_attaches_a_bill_document_when_creating_an_expense(): void
    {
        $this->actingAs(User::factory()->create());
        $file = UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf');

        Livewire::test(ListExpenses::class)
            ->callAction(CreateAction::class, data: [
                'expended_at' => '2026-01-15',
                'category' => ExpenseCategory::Good->value,
                'price' => 42.5,
                'quantity' => 1,
                'taxable' => false,
                'bill' => $file,
            ])
            ->assertHasNoFormErrors();

        $expense = Expense::sole();
        $document = $expense->documents()->sole();

        $this->assertSame('receipt.pdf', $document->filename);
        Storage::assertExists($document->path);
    }

    #[Test]
    public function it_replaces_the_bill_document_when_uploading_a_new_one(): void
    {
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);
        $original = Document::factory()->for($expense, 'documentable')->create(['path' => 'documents/old.pdf']);
        Storage::put('documents/old.pdf', 'old content');
        Storage::put('documents/new.pdf', 'new content');

        ExpenseResource::syncBillDocument($expense, ['bill' => 'documents/new.pdf', 'bill_original_name' => 'new-receipt.pdf']);

        $this->assertModelMissing($original);
        Storage::assertMissing('documents/old.pdf');
        $document = $expense->documents()->sole();
        $this->assertSame('documents/new.pdf', $document->path);
        $this->assertSame('new-receipt.pdf', $document->filename);
        Storage::assertExists($document->path);
    }

    #[Test]
    public function it_removes_the_bill_document_when_the_upload_is_cleared(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);
        Document::factory()->for($expense, 'documentable')->create();

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(EditAction::class)->table($expense), data: ['bill' => null])
            ->assertHasNoFormErrors();

        $this->assertSame(0, $expense->documents()->count());
    }

    #[Test]
    public function it_keeps_the_bill_document_untouched_when_updating_other_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);
        $document = Document::factory()->for($expense, 'documentable')->create();

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(EditAction::class)->table($expense), data: ['description' => 'New description'])
            ->assertHasNoFormErrors();

        $this->assertSame(1, $expense->documents()->count());
        $this->assertTrue($expense->documents()->sole()->is($document));
    }

    #[Test]
    public function it_replicates_an_expense_without_carrying_over_its_bill_document(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = Expense::factory()->create(['category' => ExpenseCategory::Good]);
        Document::factory()->for($expense, 'documentable')->create();

        Livewire::test(ListExpenses::class, ['activeTab' => 'all'])
            ->callAction(TestAction::make(ReplicateAction::class)->table($expense))
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('expenses', 2);
        $replica = Expense::where('id', '!=', $expense->id)->sole();
        $this->assertSame(0, $replica->documents()->count());
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }
}
