<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\TagServiceInterface;
use App\Services\TagService;
use App\Transformers\TagTransformer;
use App\Contracts\Services\TranslationServiceInterface;
use App\Services\TranslationService;
use App\Transformers\TranslationTransformer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TagServiceInterface::class, TagService::class);
        $this->app->bind(TagTransformer::class, TagTransformer::class);
        
        // Add bindings for Translation service and transformer
        $this->app->bind(TranslationServiceInterface::class, TranslationService::class);
        $this->app->bind(TranslationTransformer::class, TranslationTransformer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
