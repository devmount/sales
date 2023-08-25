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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_at')->nullable();
            $table->date('due_at')->nullable();
            $table->decimal('minimum', 7, 2, true)->nullable();
            $table->decimal('scope', 7, 2, true)->nullable();
            $table->decimal('price', 8, 2, true);
            $table->string('pricing_unit')->nullable();
            $table->boolean('aborted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
