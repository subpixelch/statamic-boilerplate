<?php

namespace Statamic\Contracts\Assets;

use Statamic\Contracts\CP\Editable;

interface AssetContainer extends Editable
{
    /**
     * Get or set the uuid
     *
     * @param null|string $uuid
     * @return string
     */
    public function uuid($uuid = null);

    /**
     * Get or set the handle
     *
     * @param null|string $handle
     * @return string
     */
    public function handle($handle = null);

    /**
     * Get or set the driver
     *
     * @param  null|string $driver
     * @return string
     */
    public function driver($driver = null);

    /**
     * Get or set the title
     *
     * @param null|string $title
     * @return string
     */
    public function title($title = null);

    /**
     * Get or set the path
     *
     * @param null|string $path
     * @return string
     */
    public function path($path = null);

    /**
     * Get the full resolved path
     *
     * @return string
     */
    public function resolvedPath();

    /**
     * Get or set the URL to this location
     *
     * @return null|string
     */
    public function url($url = null);

    /**
     * Get all the assets in this container
     *
     * @return \Statamic\Assets\AssetCollection
     */
    public function assets();

    /**
     * Get all the folders in this container
     *
     * @return \Statamic\Contracts\Assets\AssetFolder[]
     */
    public function folders();

    /**
     * Get a single folder in this container
     *
     * @param string $folder
     * @return \Statamic\Contracts\Assets\AssetFolder
     */
    public function folder($folder);

    /**
     * Add a folder to this container
     *
     * @param string $name
     * @param \Statamic\Contracts\Assets\AssetFolder $folder
     */
    public function addFolder($name, $folder);

    /**
     * Remove a folder from this container
     *
     * @param string $name
     */
    public function removeFolder($name);

    /**
     * Save the container
     *
     * @return mixed
     */
    public function save();

    /**
     * Delete the container
     *
     * @return mixed
     */
    public function delete();

    /**
     * Get or set the fieldset to be used by assets in this container
     *
     * @param string $fieldset
     */
    public function fieldset($fieldset = null);

    /**
     * Sync the assets with the files
     *
     * @return mixed
     */
    public function sync();
}
