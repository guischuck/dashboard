<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GoogleDocumentAiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleDocumentAiService::class, function ($app) {
            return new GoogleDocumentAiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
