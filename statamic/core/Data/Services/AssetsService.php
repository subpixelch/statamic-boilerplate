<?php

namespace Statamic\Data\Services;

use Statamic\Assets\AssetCollection;
use Statamic\Contracts\Assets\Asset;

class AssetsService extends BaseService
{
    /**
     * The repo key
     *
     * @var string
     */
    protected $repo = 'assets';

    /**
     * Get all assets
     *
     * @return AssetCollection
     */
    public function all()
    {
        return collect_assets($this->repo()->repos()->flatMap(function ($repo) {
            return $repo->getItems();
        }));
    }

    /**
     * Get all assets from a folder
     *
     * @param string $container
     * @param string $folder
     * @return AssetCollection
     */
    public function folder($container, $folder)
    {
        $key = rtrim(join('/', [$container, $folder]), '/');

        return collect_assets($this->repo()->repo($key)->getItems());
    }

    /**
     * Get all assets from a container
     *
     * @param string $container
     * @return AssetCollection
     */
    public function container($container)
    {
        return $this->repo()->repos()->filter(function ($repo) use ($container) {
            return preg_match('#^'.$container.'\/?\b#', $repo->key());
        })->reduce(function ($assets, $repo) {
            return $assets->merge($repo->getItems());
        }, collect_assets());
    }

    /**
     * Get an asset by ID
     *
     * @param string $id
     * @return Asset
     */
    public function id($id)
    {
        // Find the folder that holds the requested asset
        $repo = $this->repo()->repos()->first(function ($key, $repo) use ($id) {
             return $repo->getPaths()->has($id);
        });

        // Asset isn't found in any folder.
        if (! $repo) {
            return;
        }

        return $repo->getItem($id);
    }
}