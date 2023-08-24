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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expended_at'); // date NOT NULL,
            $table->decimal('price', 6, 2, true); // real NOT NULL,
            $table->boolean('taxable')->default(true); // boolean DEFAULT TRUE NOT NULL, -- former: transitory, must be negated
            $table->decimal('vat', 4, 2, true)->nullable(); // real,
            $table->integer('quantity'); // smallint DEFAULT 1 NOT NULL,
            $table->string('category'); // category DEFAULT 'good' NOT NULL, ('vat', 'good', 'service', 'tax')
            $table->text('description')->nullable(); // text,
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
