<?php

namespace Statamic\Stache\Drivers;

use Statamic\API\YAML;
use Statamic\API\Taxonomy;
use Statamic\Stache\Repository;

class TaxonomiesDriver extends AbstractDriver
{
    protected $relatable = false;

    public function getFilesystemRoot()
    {
        return 'taxonomies';
    }

    public function createItem($path, $contents)
    {
        $folder = Taxonomy::create(explode('/', $path)[1]);

        $folder->data(YAML::parse($contents));

        return $folder;
    }

    public function getItemId($item, $path)
    {
        return explode('/', $path)[1];
    }

    public function isMatchingFile($file)
    {
        return $file['basename'] === 'folder.yaml';
    }

    public function toPersistentArray($repo)
    {
        return [
            'meta' => [
                'paths' => $repo->getPaths()->all()
            ],
            'items' => ['data' => $repo->getItems()]
        ];
    }

    /**
     * Get the locale based on the path
     *
     * @param string $path
     * @return string
     */
    public function getLocaleFromPath($path)
    {
        dd('taxonomy locale from path', $path);
    }

    /**
     * Get the localized URL
     *
     * @param        $locale
     * @param array  $data
     * @param string $path
     * @return string
     */
    public function getLocalizedUri($locale, $data, $path)
    {
        dd('taxonomy locale', $path, $data);
    }
}
