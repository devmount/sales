<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::whereIn('field', ['logo', 'signature'])->update(['type' => 'image']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('field', ['logo', 'signature'])->update(['type' => 'textarea']);
    }
};
