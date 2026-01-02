<?php

namespace App\Console\Commands;

use App\Enums\ExpenseCategory;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Gift;
use App\Models\Invoice;
use App\Models\Position;
use App\Models\Project;
use Illuminate\Console\Command;

class AnonymizePersonalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:anonymize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace all personal data entries as well as all titles and descriptions with fake placeholder text';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ('production' == config('app.env')) {
            abort(403, 'This command should only be run in local/testing environments!');
            exit;
        }

        $clients = Client::all();
        $projects = Project::all();
        $estimates = Estimate::all();
        $invoices = Invoice::all();
        $positions = Position::all();
        $expenses = Expense::all();
        $gifts = Gift::all();
        $total = array_sum([
            $clients->count(),
            $projects->count(),
            $estimates->count(),
            $invoices->count(),
            $positions->count(),
            $expenses->count(),
            $gifts->count(),
        ]);
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        foreach ($clients as $obj) {
            $name = fake()->company();
            $obj->name = $name;
            $obj->short = str($name)->substr(0, 2)->upper();
            $obj->street = fake()->streetAddress();
            $obj->zip = fake()->postcode();
            $obj->city = fake()->city();
            $obj->country = 'DE';
            $obj->email = fake()->companyEmail();
            $obj->phone = fake()->phoneNumber();
            $obj->save();
            $bar->advance();
        }
        foreach ($projects as $obj) {
            $obj->title = str(fake()->words(3, true))->headline();
            $obj->description = implode("\n", fake()->sentences(2));
            $obj->save();
            $bar->advance();
        }
        foreach ($estimates as $obj) {
            $obj->title = str(fake()->words(4, true))->ucfirst();
            $obj->description = implode("\n", fake()->sentences(2));
            $obj->save();
            $bar->advance();
        }
        foreach ($invoices as $obj) {
            $obj->title = str(fake()->words(3, true))->headline();
            $obj->description = implode("\n", fake()->sentences(2));
            $obj->save();
            $bar->advance();
        }
        foreach ($positions as $obj) {
            $obj->description = implode("\n", fake()->sentences(2));
            $obj->save();
            $bar->advance();
        }
        foreach ($expenses as $obj) {
            if (!in_array($obj->category, ExpenseCategory::taxCategories())) {
                $obj->description = fake()->sentence();
                $obj->save();
                $bar->advance();
            }
        }
        foreach ($gifts as $obj) {
            $obj->subject = fake()->sentence();
            $obj->name = fake()->name();
            $obj->email = fake()->email();
            $obj->save();
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->line("Processing finished. $total records updated.");
    }
}
