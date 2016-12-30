<?php

namespace Statamic\Contracts\Extend\Management;

interface ComposerManager
{
    /**
     * Read the contents of a composer.json
     *
     * @return array
     */
    public function read();

    /**
     * Save an array to a composer.json
     *
     * @param array $contents
     * @return mixed
     */
    public function save($contents);

    /**
     * Get or set the path to the composer.json
     *
     * @param string|null $path
     * @return mixed
     */
    public function path($path = null);

    /**
     * Run composer update
     *
     * @param array|null $packages Packages to specifically update
     * @return mixed
     */
    public function update($packages = null);
}
