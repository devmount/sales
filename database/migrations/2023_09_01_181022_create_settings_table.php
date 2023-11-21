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
            $table->string('field')->primary();
            $table->text('value')->nullable();
            $table->string('type');
            $table->json('attributes')->nullable();
            $table->integer('weight')->default(0);
            $table->timestamps();
        });
        Setting::create(['field' => 'accountHolder', 'type' => 'text',     'weight' => 100]);
        Setting::create(['field' => 'address',       'type' => 'textarea', 'weight' =>  30]);
        Setting::create(['field' => 'bank',          'type' => 'text',     'weight' =>  90]);
        Setting::create(['field' => 'bic',           'type' => 'text',     'weight' =>  80]);
        Setting::create(['field' => 'company',       'type' => 'text',     'weight' =>  20]);
        Setting::create(['field' => 'email',         'type' => 'email',    'weight' =>  40]);
        Setting::create(['field' => 'iban',          'type' => 'text',     'weight' =>  70]);
        Setting::create(['field' => 'logo',          'type' => 'textarea', 'weight' => 130]);
        Setting::create(['field' => 'name',          'type' => 'text',     'weight' =>  10]);
        Setting::create(['field' => 'phone',         'type' => 'tel',      'weight' =>  50]);
        Setting::create(['field' => 'signature',     'type' => 'textarea', 'weight' => 140]);
        Setting::create(['field' => 'taxOffice',     'type' => 'text',     'weight' => 110]);
        Setting::create(['field' => 'vatId',         'type' => 'text',     'weight' => 120]);
        Setting::create(['field' => 'vatRate',       'type' => 'number',   'weight' => 130]);
        Setting::create(['field' => 'website',       'type' => 'url',      'weight' =>  60]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
