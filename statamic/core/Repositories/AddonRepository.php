<?php

namespace Statamic\Repositories;

use Statamic\API\Folder;
use Statamic\API\Path;
use Statamic\API\Str;
use Statamic\FileCollection;

class AddonRepository
{
    /**
     * @var \Statamic\FileCollection
     */
    private $files;

    /**
     * Create a new repo
     */
    public function __construct(FileCollection $files)
    {
        $this->files = $files;
    }

    /**
     * Get the collection of files
     *
     * @return \Statamic\FileCollection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get an array of all the paths transformed into equivalent classes
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->files->transform(function($path) {
            $class = preg_replace('/^(site\/addons|statamic\/bundles)/', '', $path);
            $class = str_replace(['/', '.php'], ['\\', ''], $class);

            return 'Statamic\\Addons' . $class;
        })->all();
    }

    /**
     * Filter by the ending of the path
     *
     * @param string $end  The end of the path, eg. ServiceProvider.php or cp.yaml
     * @return $this
     */
    public function filter($end)
    {
        $this->files = $this->files->filter(function($path) use ($end) {
            return Str::endsWith($path, $end);
        });

        return $this;
    }

    /**
     * Filter by addons only
     *
     * @return $this
     */
    public function addons()
    {
        $filter_path = Path::makeRelative(addons_path());

        $this->files = $this->files->filter(function($path) use ($filter_path) {
            return Str::startsWith($path, $filter_path);
        });

        return $this;
    }

    /**
     * Filter by bundles only
     *
     * @return $this
     */
    public function bundles()
    {
        $filter_path = Path::makeRelative(bundles_path());

        $this->files = $this->files->filter(function($path) use ($filter_path) {
            return Str::startsWith($path, $filter_path);
        });

        return $this;
    }
}
