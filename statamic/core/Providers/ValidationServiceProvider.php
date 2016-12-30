<?php

namespace Statamic\Providers;

use Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Validator::extend('handle_exists', function ($attribute, $value, $parameters, $validator) {
            return ! $this->assetContainerExists($value);
        });
    }

    /**
     * Check if the AssetContainer exists.
     *
     * @param  string  $value
     * @return boolean
     */
    private function assetContainerExists($value)
    {
        return (bool) \Statamic\API\AssetContainer::find($value);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
