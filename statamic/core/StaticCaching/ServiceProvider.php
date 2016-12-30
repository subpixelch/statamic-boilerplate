<?php

namespace Statamic\StaticCaching;

use Statamic\API\Config;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register services
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Cacher::class, function () {
            return (Config::get('caching.static_caching_type') === 'file')
                ? app(FileCacher::class)
                : app(ApplicationCacher::class);
        });

        $this->commands(ClearStaticCommand::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Cacher::class];
    }
}
