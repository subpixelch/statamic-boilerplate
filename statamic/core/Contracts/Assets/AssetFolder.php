<?php

namespace Statamic\Contracts\Assets;

use Statamic\Contracts\Data\DataFolder;

interface AssetFolder extends DataFolder
{
    /**
     * Get the container where this folder is located
     *
     * @return \Statamic\Contracts\Assets\AssetContainer
     */
    public function container();

    /**
     * Get the assets in the folder
     *
     * @return \Statamic\Assets\AssetCollection
     */
    public function assets();

    /**
     * Add an asset to the folder
     *
     * @param string $key
     * @param \Statamic\Contracts\Assets\Asset $asset
     */
    public function addAsset($key, $asset);

    /**
     * Remove an asset from the folder
     *
     * @param string $key
     */
    public function removeAsset($key);

    /**
     * Get the nested folders
     *
     * @return \Statamic\Contracts\Assets\AssetFolder[]
     */
    public function folders();

    /**
     * Create a nested folder
     *
     * @param string $basename
     * @return \Statamic\Contracts\Assets\AssetFolder
     */
    public function createFolder($basename);

    /**
     * Get the parent folder
     *
     * @return null|\Statamic\Contracts\Assets\AssetFolder
     */
    public function parent();

    /**
     * Get the number of assets in the folder
     *
     * @return int
     */
    public function count();

    /**
     * Get the full resolved path, including the container
     *
     * @return string
     */
    public function resolvedPath();
}
