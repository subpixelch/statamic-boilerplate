<?php

namespace Statamic;

use Illuminate\Foundation\Application as Laravel;

class Application extends Laravel
{
    /**
     * Get the path to the application "app" directory.
     *
     * @return string
     */
    public function path()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'core';
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function publicPath()
    {
        return SITE_ROOT;
    }
}
