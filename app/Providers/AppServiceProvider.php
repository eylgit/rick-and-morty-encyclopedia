<?php

namespace App\Providers;

use App\RickAndMorty\ApiClient;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One ApiClient for the whole request, built from config/rickandmorty.php.
        $this->app->singleton(ApiClient::class, fn () => new ApiClient(
            baseUrl: config('rickandmorty.base_url'),
            timeout: config('rickandmorty.timeout'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
