<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
 //use Illuminate\Support\Facades\URL;

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
        // Memaksa HTTPS jika menggunakan Ngrok agar CSS/JS tidak terblokir
      //  if (str_contains(config('app.url'), 'ngrok-free.dev') || str_contains(config('app.url'), 'ngrok-free.app')) {
        //    URL::forceScheme('https');
      //  }
    }
}

//ngrok http 8000 --host-header="smearier-evolutionarily-amanda.ngrok-free.dev"