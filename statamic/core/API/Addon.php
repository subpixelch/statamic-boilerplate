<?php

namespace Statamic\API;

class Addon
{
    /**
     * @return \Statamic\Contracts\Extend\Management\AddonManager
     */
    public static function manager()
    {
        return app('Statamic\Contracts\Extend\Management\AddonManager');
    }

    /**
     * Update dependencies for all addons, one addon, or multiple addons.
     *
     * @param string|array|null $packages
     */
    public static function updateDependencies($packages = null)
    {
        self::manager()->updateDependencies($packages);
    }
}
