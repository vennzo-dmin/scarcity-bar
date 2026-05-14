<?php

namespace App\Providers;

use App\Domain\Notifications\NotificationEngine;
use App\Domain\Notifications\ProviderResolver;
use App\Domain\Notifications\TemplateRenderer;
use Illuminate\Support\ServiceProvider;

class BisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/bis.php', 'bis');

        $this->app->singleton(TemplateRenderer::class);
        $this->app->singleton(ProviderResolver::class);

        $this->app->singleton(NotificationEngine::class, function ($app) {
            return new NotificationEngine(
                $app->make(TemplateRenderer::class),
                $app->make(ProviderResolver::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
