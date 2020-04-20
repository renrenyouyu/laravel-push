<?php

namespace Renrenyouyu\LaravelPush;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class PushServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/push.php', 'push');

        $this->app->singleton('push', function () {
            return new Push(config('push'));
        });
    }

    public function boot()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([
                    dirname(__DIR__).'/config/push.php' => config_path('push.php'),
                ],
                'push'
            );
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('push');
        }
    }
}
