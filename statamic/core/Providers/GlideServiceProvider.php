<?php

namespace Statamic\Providers;

use Statamic\API\File;
use League\Glide\Server;
use Statamic\API\Config;
use League\Glide\ServerFactory;
use Statamic\Imaging\ImageGenerator;
use Statamic\Imaging\GlideUrlBuilder;
use Statamic\Imaging\StaticUrlBuilder;
use Illuminate\Support\ServiceProvider;
use Statamic\Imaging\GlideImageManipulator;
use Statamic\Contracts\Imaging\ImageManipulator;
use League\Glide\Responses\LaravelResponseFactory;

class GlideServiceProvider extends ServiceProvider
{
    public $defer = true;

    public function register()
    {
        $this->app->bind(ImageManipulator::class, function () {
            return new GlideImageManipulator($this->getBuilder());
        });

        $this->app->singleton(Server::class, function () {
            return ServerFactory::create([
                'source'   => path(STATAMIC_ROOT), // this gets overriden on the fly by the image generator
                'cache'    => File::disk('glide')->filesystem()->getDriver(),
                'base_url' => Config::get('assets.image_manipulation_route', 'img'),
                'response' => new LaravelResponseFactory(app('request')),
                'driver'   => Config::get('assets.image_manipulation_driver'),
                'cache_with_file_extensions' => true,
            ]);
        });
    }

    private function getBuilder()
    {
        $route = Config::get('assets.image_manipulation_route');

        if (Config::get('assets.image_manipulation_cached')) {
            return new StaticUrlBuilder($this->app->make(ImageGenerator::class), [
                'route' => $route
            ]);
        }

        return new GlideUrlBuilder([
            'key' => (Config::get('assets.image_manipulation_secure')) ? Config::getAppKey() : null,
            'route' => $route
        ]);
    }

    public function provides()
    {
        return [ImageManipulator::class, Server::class];
    }
}
