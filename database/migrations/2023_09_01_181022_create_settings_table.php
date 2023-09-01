<?php

use App\Models\Setting;
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
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type');
            $table->json('attributes')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
        Setting::create(['key' => 'name',          'type' => 'text',     'sort' => 1]);
        Setting::create(['key' => 'address',       'type' => 'textarea', 'sort' => 2]);
        Setting::create(['key' => 'email',         'type' => 'email',    'sort' => 3]);
        Setting::create(['key' => 'phone',         'type' => 'tel',      'sort' => 4]);
        Setting::create(['key' => 'website',       'type' => 'url',      'sort' => 5]);
        Setting::create(['key' => 'iban',          'type' => 'text',     'sort' => 6]);
        Setting::create(['key' => 'bic',           'type' => 'text',     'sort' => 7]);
        Setting::create(['key' => 'bank',          'type' => 'text',     'sort' => 8]);
        Setting::create(['key' => 'accountHolder', 'type' => 'text',     'sort' => 9]);
        Setting::create(['key' => 'taxOffice',     'type' => 'text',     'sort' => 10]);
        Setting::create(['key' => 'vatId',         'type' => 'text',     'sort' => 11]);
        Setting::create(['key' => 'logo',          'type' => 'textarea', 'sort' => 12]);
        Setting::create(['key' => 'signature',     'type' => 'textarea', 'sort' => 13]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
