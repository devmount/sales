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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->string('country')->nullable()->after('address');
            $table->string('city')->nullable()->after('address');
            $table->string('zip')->nullable()->after('address');
            $table->string('street')->nullable()->after('address');
            $table->string('vat_id', 16)->nullable()->after('language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'street',
                'zip',
                'city',
                'country',
                'vat_id',
            ]);
        });
    }
};
