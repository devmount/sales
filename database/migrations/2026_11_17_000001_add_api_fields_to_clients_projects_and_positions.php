<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('api_config')->nullable()->after('vat_id');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('api_reference')->nullable()->after('aborted');
        });
        Schema::table('positions', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('remote');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('api_config');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('api_reference');
        });
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    }
};
