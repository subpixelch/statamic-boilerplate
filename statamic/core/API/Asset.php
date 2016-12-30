<?php

namespace Statamic\API;

use Statamic\Assets\AssetCollection;
use Statamic\Data\Services\AssetsService;
use Statamic\Contracts\Assets\AssetFactory;

class Asset
{
    /**
     * Get an asset by ID
     *
     * @param string $id
     * @return \Statamic\Contracts\Assets\Asset
     */
    public static function find($id)
    {
        return app(AssetsService::class)->id($id);
    }

    /**
     * Get all assets
     *
     * @return AssetCollection
     */
    public static function all()
    {
        return app(AssetsService::class)->all();
    }

    /**
     * Get all assets in a folder
     *
     * @param string $folder
     * @param string $container
     * @return AssetCollection
     */
    public static function whereFolder($folder, $container)
    {
        return app(AssetsService::class)->folder($container, $folder);
    }

    /**
     * Get all assets in a container
     *
     * @param string $container
     * @return AssetCollection
     */
    public static function whereContainer($container)
    {
        return app(AssetsService::class)->container($container);
    }

    /**
     * Get an asset by its path
     *
     * @param string      $path
     * @return Asset
     */
    public static function wherePath($path)
    {
        return self::all()->filter(function ($asset) use ($path) {
            return $asset->resolvedPath() === $path;
        })->first();
    }

    /**
     * @param string|null $uuid
     * @return \Statamic\Contracts\Assets\AssetFactory
     */
    public static function create($uuid = null)
    {
        return app(AssetFactory::class)->create($uuid);
    }

    /**
     * Get a raw asset by its UUID
     *
     * @param string      $uuid
     * @return \Statamic\Contracts\Assets\Asset
     * @deprecated since 2.1
     */
    public static function uuidRaw($uuid)
    {
        \Log::notice('Asset::uuidRaw() is deprecated. Use Asset::find()');

        return self::find($uuid);
    }

    /**
     * Get an asset by its UUID
     *
     * @param string      $uuid
     * @return array
     * @deprecated since 2.1
     */
    public static function uuid($uuid)
    {
        \Log::notice('Asset::uuid() is deprecated. Use Asset::find()->toArray()');

        return self::find($uuid)->toArray();
    }

    /**
     * Get an asset by its path
     *
     * @param string      $path
     * @return Asset
     * @deprecated since 2.1
     */
    public static function path($path)
    {
        \Log::notice('Asset::path() is deprecated. Use Asset::wherePath()');

        return self::wherePath($path);
    }
}
