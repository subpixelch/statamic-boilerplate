<?php

namespace Statamic\Extend\Installer;

use Statamic\Extend\Addon;

class Installer
{
    private $addon;

    public function __construct($addon_name)
    {
        $this->addon = new Addon($addon_name);
        $this->folder = addons_path($addon_name . '/');
    }

    public function install()
    {
    }
}
