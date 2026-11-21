<?php

namespace PingoLine\Laravel;

use Illuminate\Support\ServiceProvider;

class PingoLineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pingoline.php', 'pingoline');

        $this->app->singleton(PingoLineClient::class, function ($app) {
            $config = $app['config']['pingoline'] ?? [];

            return new PingoLineClient(
                apiKey: (string) ($config['api_key'] ?? ''),
                apiUrl: (string) ($config['api_url'] ?? 'https://pingoline.com/api/v1'),
                webhookSecret: ! empty($config['webhook_secret']) ? (string) $config['webhook_secret'] : null,
                timeout: (int) ($config['timeout'] ?? 15),
                retry: (array) ($config['retry'] ?? ['times' => 2, 'sleep' => 200])
            );
        });

        $this->app->alias(PingoLineClient::class, 'pingoline');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pingoline.php' => $this->app->configPath('pingoline.php'),
            ], 'pingoline-config');
        }
    }
}
