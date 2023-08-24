<?php

namespace App\Providers;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Filament only saves valid data to models so the models can be unguarded safely
        Model::unguard();

        // Customize Filament colors
        FilamentColor::register([
            'teal' => Color::Teal,
            'blue' => Color::Blue,
            'purple' => Color::Purple,
            'rose' => Color::Rose,
        ]);
    }
}
