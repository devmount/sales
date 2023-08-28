<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('price', 8, 2, true);
            $table->string('pricing_unit');
            $table->decimal('discount', 8, 2, true)->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('transitory')->default(false);
            $table->boolean('undated')->default(false);
            $table->decimal('vat', 4, 2, true)->nullable();
            $table->date('invoiced_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->decimal('deduction', 8, 2, true)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
