<?php

namespace Statamic\Contracts\Extend\Management;

interface AddonManager
{
    /**
     * Install an addon
     *
     * @param $addon
     * @return mixed
     */
    public function install($addon);

    /**
     * Uninstall an addon
     *
     * @param $addon
     * @return mixed
     */
    public function uninstall($addon);

    /**
     * Get the ComposerManager instance
     *
     * @return ComposerManager
     */
    public function composer();

    /**
     * Update dependencies for all addons, one addon, or multiple addons.
     *
     * @param string|array|null $packages
     * @return mixed
     */
    public function updateDependencies($packages = null);

    /**
     * Get all addons with composer.json files
     *
     * @return array  An array of package names
     */
    public function packages();
}
