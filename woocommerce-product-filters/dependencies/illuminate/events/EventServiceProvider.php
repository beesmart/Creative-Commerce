<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Events;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\ServiceProvider;
class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }
}
