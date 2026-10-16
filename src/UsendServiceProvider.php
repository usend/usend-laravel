<?php

declare(strict_types=1);

namespace Usend\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Usend\Client;

/**
 * Registers the usend client and a `usend` mail transport, so `MAIL_MAILER=usend` sends every Mailable through the API.
 */
final class UsendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/usend.php', 'usend');

        $this->app->singleton(Client::class, function ($app): Client {
            $config = $app['config']->get('usend', []);
            if (empty($config['key'])) {
                throw new \InvalidArgumentException('Set USEND_API_KEY (or usend.key) to use the usend client.');
            }

            return new Client($config['key'], ['baseUrl' => $config['base_url'] ?? Client::DEFAULT_BASE_URL]);
        });
        $this->app->alias(Client::class, 'usend');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/usend.php' => config_path('usend.php')], 'usend-config');

        Mail::extend('usend', function (array $config = []) {
            return new UsendTransport($this->app->make(Client::class));
        });
    }
}
