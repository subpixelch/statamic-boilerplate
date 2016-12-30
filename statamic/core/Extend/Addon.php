<?php

namespace Statamic\Extend;

/**
 * A generic addon class
 *
 * This is used for temporary leveraging an addon without requiring any specific aspect.
 * For example, when displaying an addon's setting in the control panel, we need to
 * get the addon's config using the getConfig() method. We don't care which
 * aspects are in the addon (Modifier Tags, etc) - we just want to use
 * the Extensible methods through it.
 *
 * In the past, some developers would have extended this class to gain access to addon
 * helper methods. Instead, it's recommended to just use the Extensible trait.
 */
class Addon
{
    /**
     * Provides access to addon helper methods
     */
    use Extensible;

    /**
     * @var string
     */
    protected $addon_name;

    /**
     * Create a new Addon instance
     *
     * @param string|null $name  Name of the addon
     */
    public function __construct($name = null)
    {
        $this->addon_name = $name ?: $this->getAddonClassName();

        $this->bootstrap();
        $this->init();
    }
}
