<?php

namespace Asvri\Laravel;

use Asvri\Client;
use Asvri\Laravel\Console\RegisterWebhookCommand;
use Illuminate\Support\ServiceProvider;

class AsvriServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/asvri.php', 'asvri');

        // the client is made on first use, so an app without a key still boots (and says what's missing when called)
        $this->app->singleton(Client::class, function ($app) {
            $c = $app['config']['asvri'];
            $key = (string) ($c['secret_key'] ?? '');
            if ($key === '') {
                throw new \RuntimeException('ASVRI_SECRET_KEY is not set. Add your secret key (sk_test_… or sk_live_…) from ASVRI → Settings → API access to .env.');
            }
            return new Client($key, array_filter([
                'api_base' => $c['api_base'] ?? null,
                'api_ext' => $c['api_ext'] ?? null,
                'timeout' => $c['timeout'] ?? null,
                'max_retries' => $c['max_retries'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''));
        });
        $this->app->alias(Client::class, 'asvri.client');
        $this->app->singleton(AsvriManager::class, fn ($app) => new AsvriManager(fn () => $app->make(Client::class)));
        $this->app->alias(AsvriManager::class, 'asvri');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/asvri.php' => config_path('asvri.php')], 'asvri-config');
            $this->commands([RegisterWebhookCommand::class]);
        }
        if (config('asvri.webhook.path')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhook.php');
        }
    }
}
