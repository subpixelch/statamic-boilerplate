<?php

namespace Statamic\Providers;

use Statamic\API\Path;
use Statamic\API\Folder;
use Illuminate\Support\ServiceProvider;
use Statamic\Repositories\AddonRepository;
use Statamic\Extend\Management\AddonManager;
use Statamic\Extend\Management\ComposerManager;

class AddonServiceProvider extends ServiceProvider
{
    /**
     * @var Statamic\FileCollection
     */
    private $files;

    /**
     * @var array
     */
    private $translated = [];

    public function boot()
    {
        $this->loadTranslations();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->files = $this->findAddonFiles();

        $this->app->bind('Statamic\Repositories\AddonRepository', function () {
            return $this->createRepo();
        });

        $this->app->singleton('Statamic\Contracts\Extend\Management\AddonManager', function() {
            return new AddonManager(app('Statamic\Contracts\Extend\Management\ComposerManager'));
        });

        $this->app->bind('Statamic\Contracts\Extend\Management\ComposerManager', function() {
            return new ComposerManager;
        });

        $this->app->singleton('Statamic\Extend\Contextual\Store');

        $this->registerAddonServiceProviders();
    }

    private function registerAddonServiceProviders()
    {
        foreach ($this->getAddonProviders() as $provider) {
            $provider = $this->app->resolveProviderClass($provider);

            if (! empty($provider->providers)) {
                call_user_func([$provider, 'registerAdditionalProviders']);
            }

            if (! empty($provider->aliases)) {
                call_user_func([$provider, 'registerAdditionalAliases']);
            }

            $this->app->register($provider);
        }
    }

    private function createRepo()
    {
        return new AddonRepository($this->files);
    }

    private function findAddonFiles()
    {
        $files = [];

        foreach ([addons_path(), bundles_path()] as $path) {
            $files = array_merge($files, Folder::getFilesRecursively($path));
        }

        return collect_files($files);
    }

    /**
     * Get the addon defined service providers
     */
    private function getAddonProviders()
    {
        return $this->createRepo()->filter('ServiceProvider.php')->getClasses();
    }

    private function loadTranslations()
    {
        $files = addon_repo()->getFiles()->filter(function ($path) {
            return preg_match('/resources\/lang/', $path);
        });

        $files->each(function ($path) {
            $parts = explode('/', $path);

            $addon = $parts[2];

            // move on if we've already added this addon
            if (in_array($addon, $this->translated)) {
                return true;
            }

            $namespace = 'addons.'.$addon;

            $parts = array_slice($parts, 0, 5);
            $path = join('/', $parts);

            $this->loadTranslationsFrom(root_path($path), $namespace);
        });
    }
}
