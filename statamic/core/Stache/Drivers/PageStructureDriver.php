<?php

namespace Statamic\Stache\Drivers;

use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\Path;

class PageStructureDriver extends AbstractDriver
{
    protected $relatable = false;

    public function getFilesystemRoot()
    {
        return 'pages';
    }

    public function createItem($path, $contents)
    {
        $url = URL::buildFromPath($path);

        return [
            'url'    => $url,
            'parent' => ($url == '/') ? null : URL::parent($url),
            'depth'  => ($url == '/') ? 0 : substr_count($url, '/'),
            'status' => Path::status($path),
        ];
    }

    public function getItemId($item, $path)
    {
        return $this->stache->repo('pages')->getIdByPath($path);
    }

    public function isMatchingFile($file)
    {
        return $file['filename'] === 'index';
    }

    /**
     * Get the locale based on the path
     *
     * @param string $path
     * @return string
     */
    public function getLocaleFromPath($path)
    {
        //
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
        //
    }

    public function toPersistentArray($repo)
    {
        return [
            'meta' => [
                'paths' => $repo->getPaths()->all(),
                'uris' => $repo->getUris()->all(),
            ],
            'items' => ['data' => $repo->getItems()]
        ];
    }
}
