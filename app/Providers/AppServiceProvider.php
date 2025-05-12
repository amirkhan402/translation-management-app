<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\TagServiceInterface;
use App\Services\TagService;
use App\Transformers\TagTransformer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TagServiceInterface::class, TagService::class);
        $this->app->singleton(TagTransformer::class, TagTransformer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
