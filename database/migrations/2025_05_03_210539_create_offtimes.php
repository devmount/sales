<?php

use App\Enums\OfftimeCategory;
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
        Schema::create('offtimes', function (Blueprint $table) {
            $table->id();
            $table->date('start')->index();
            $table->date('end')->index()->nullable();
            $table->enum('category', array_column(OfftimeCategory::cases(), 'value'))->index();
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offtimes');
    }
};
