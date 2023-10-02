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
        Setting::create(['key' => 'name',          'type' => 'text',     'sort' =>  10]);
        Setting::create(['key' => 'company',       'type' => 'text',     'sort' =>  20]);
        Setting::create(['key' => 'address',       'type' => 'textarea', 'sort' =>  30]);
        Setting::create(['key' => 'email',         'type' => 'email',    'sort' =>  40]);
        Setting::create(['key' => 'phone',         'type' => 'tel',      'sort' =>  50]);
        Setting::create(['key' => 'website',       'type' => 'url',      'sort' =>  60]);
        Setting::create(['key' => 'iban',          'type' => 'text',     'sort' =>  70]);
        Setting::create(['key' => 'bic',           'type' => 'text',     'sort' =>  80]);
        Setting::create(['key' => 'bank',          'type' => 'text',     'sort' =>  90]);
        Setting::create(['key' => 'accountHolder', 'type' => 'text',     'sort' => 100]);
        Setting::create(['key' => 'taxOffice',     'type' => 'text',     'sort' => 110]);
        Setting::create(['key' => 'vatId',         'type' => 'text',     'sort' => 120]);
        Setting::create(['key' => 'logo',          'type' => 'textarea', 'sort' => 130]);
        Setting::create(['key' => 'signature',     'type' => 'textarea', 'sort' => 140]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
