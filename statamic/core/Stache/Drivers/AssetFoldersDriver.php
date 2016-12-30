<?php

namespace Statamic\Stache\Drivers;

use Statamic\API\Folder;
use Statamic\API\Str;
use Statamic\API\YAML;
use Statamic\Assets\AssetFolder;

class AssetFoldersDriver extends AbstractDriver
{
    protected $relatable = false;

    public function getFilesystemDriver()
    {
        return Folder::disk('storage')->filesystem()->getDriver();
    }

    public function getFilesystemRoot()
    {
        return 'assets';
    }

    public function createItem($path, $contents)
    {
        $data = YAML::parse($contents);

        list($container, $folder) = explode('/', Str::removeLeft($path, 'assets/'), 2);

        $folder = Str::removeRight($folder, 'folder.yaml');
        $folder = ($folder === '') ? '/' : rtrim($folder, '/');

        return new AssetFolder($container, $folder, $data);
    }

    public function getItemId($item, $path)
    {
        return Str::removeRight($path, '/folder.yaml');
    }

    public function isMatchingFile($file)
    {
        return $file['basename'] === 'folder.yaml';
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

    /**
     * Get the locale based on the path
     *
     * @param string $path
     * @return string
     */
    public function getLocaleFromPath($path)
    {
        dd('assetfolder locale from path', $path);
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
        dd('assetfolder locale', $path, $data);
    }
}
