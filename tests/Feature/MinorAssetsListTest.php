<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Filament\Widgets\MinorAssetsList;
use App\Models\Document;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MinorAssetsListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(MinorAssetsList::class)->assertSuccessful();
    }

    #[Test]
    public function it_defaults_the_filter_to_last_year(): void
    {
        $widget = new MinorAssetsList();

        $this->assertSame(now()->year - 1, $widget->filter);
    }

    #[Test]
    public function it_only_lists_minor_assets_expenses_with_a_net_value_trackable_in_the_asset_register(): void
    {
        $year = now()->year - 1;

        $trackable = Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::MinorAssets,
            'price' => 500,
            'quantity' => 1,
            'taxable' => false,
        ]);

        // exactly at the 800 € GWG cap, must be included (§6(2) EStG: costs not exceeding 800 €)
        $atUpperBound = Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::MinorAssets,
            'price' => 800,
            'quantity' => 1,
            'taxable' => false,
        ]);

        // below the 250 € tracking threshold, must be excluded
        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::MinorAssets,
            'price' => 100,
            'quantity' => 1,
            'taxable' => false,
        ]);

        // exactly at the 250 € tracking threshold, must be excluded (§6(2) EStG: costs exceeding 250 €)
        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::MinorAssets,
            'price' => 250,
            'quantity' => 1,
            'taxable' => false,
        ]);

        // above the 800 € GWG cap, must be excluded
        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::MinorAssets,
            'price' => 801,
            'quantity' => 1,
            'taxable' => false,
        ]);

        // not a minor assets expense, must be excluded regardless of its net value
        Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::Good,
            'price' => 500,
            'quantity' => 1,
            'taxable' => false,
        ]);

        $records = (new MinorAssetsList())->getTableRecords();

        $this->assertCount(2, $records);
        $this->assertTrue($records->contains($trackable));
        $this->assertTrue($records->contains($atUpperBound));
    }

    #[Test]
    public function it_only_lists_minor_assets_expenses_from_the_selected_year(): void
    {
        $year = now()->year - 1;

        $inYear = Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::MinorAssets,
            'price' => 500,
            'quantity' => 1,
            'taxable' => false,
        ]);

        // outside the selected year, must be excluded
        Expense::factory()->create([
            'expended_at' => ($year - 1) . '-06-01',
            'category' => ExpenseCategory::MinorAssets,
            'price' => 500,
            'quantity' => 1,
            'taxable' => false,
        ]);

        $widget = new MinorAssetsList();
        $widget->filter = $year;
        $records = $widget->getTableRecords();

        $this->assertCount(1, $records);
        $this->assertTrue($records->contains($inYear));
    }

    #[Test]
    public function it_hides_the_bill_download_until_a_document_exists(): void
    {
        $this->actingAs(User::factory()->create());
        $year = now()->year - 1;
        $expense = Expense::factory()->create([
            'expended_at' => "$year-06-01",
            'category' => ExpenseCategory::MinorAssets,
            'price' => 500,
            'quantity' => 1,
            'taxable' => false,
        ]);

        Livewire::test(MinorAssetsList::class)
            ->assertTableActionHidden('bill', $expense);

        Document::factory()->for($expense, 'documentable')->create();

        Livewire::test(MinorAssetsList::class)
            ->assertTableActionVisible('bill', $expense);
    }
}
