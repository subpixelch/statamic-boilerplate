<?php

namespace Statamic\Extend\Management;

use Statamic\API\File;
use Statamic\API\Helper;
use Statamic\Contracts\Extend\Management\ComposerManager;
use Statamic\Contracts\Extend\Management\AddonManager as ManagerContract;

class AddonManager implements ManagerContract
{
    /**
     * @var ComposerManager
     */
    protected $composer;

    /**
     * @var array
     */
    protected $packages;

    /**
     * Create an AddonManager instance
     *
     * @param \Statamic\Contracts\Extend\Management\ComposerManager $composer
     */
    public function __construct(ComposerManager $composer)
    {
        $this->composer = $composer;
    }

    /**
     * Get the ComposerManager instance
     *
     * @return ComposerManager
     */
    public function composer()
    {
        return $this->composer;
    }

    /**
     * Update dependencies for all addons, one addon, or multiple addons.
     *
     * @param string|array|null $packages
     * @return mixed
     */
    public function updateDependencies($packages = null)
    {
        $packages = (is_null($packages))
            ? $this->packages()
            : Helper::ensureArray($packages);

        $this->updateComposerJson($packages);

        if (! empty($packages)) {
            $this->composer->update($packages);
        }
    }

    /**
     * Add specified packages to the `require` array in composer.json
     *
     * @param array $packages
     */
    private function updateComposerJson($packages)
    {
        $contents = $this->composer->read();

        $original = $this->composer->readOriginal();

        $requires = array_get($original, 'require');

        foreach ($packages as $package) {
            $requires[$package] = '*@dev';
        }

        $contents['require'] = $requires;

        $this->composer->save($contents);
    }

    /**
     * Get all addons with composer.json files
     *
     * @return array  An array of package names
     */
    public function packages()
    {
        if ($this->packages) {
            return $this->packages;
        }

        $addons = addon_repo()->filter('composer.json')->getFiles()->all();

        if (count($addons) === 0) {
            return [];
        }

        $packages = [];
        foreach ($addons as $path) {
            $json = json_decode(File::get($path), true);

            if ($name = array_get($json, 'name')) {
                $packages[] = $name;
            }
        }

        return $this->packages = $packages;
    }

    /**
     * Install an addon
     *
     * @param $addon
     * @return mixed
     */
    public function install($addon)
    {
        // TODO: Implement install() method.
    }

    /**
     * Uninstall an addon
     *
     * @param $addon
     * @return mixed
     */
    public function uninstall($addon)
    {
        // TODO: Implement uninstall() method.
    }
}
