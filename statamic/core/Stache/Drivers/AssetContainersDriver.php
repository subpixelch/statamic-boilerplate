<?php

namespace Statamic\Stache\Drivers;

use Statamic\API\AssetContainer;
use Statamic\API\File;
use Statamic\API\Folder;
use Statamic\API\YAML;

class AssetContainersDriver extends AbstractDriver
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

        $id = explode('/', $path)[1];
        $driver = array_get($data, 'driver', 'local');

        $container = AssetContainer::create();
        $container->id($id);
        $container->driver($driver);
        $container->path(array_get($data, 'path'));
        $container->title(array_get($data, 'title'));
        $container->fieldset(array_get($data, 'fieldset'));
        $container->url($this->getUrl($id, $driver, $data));

        return $container;
    }

    private function getUrl($id, $driver, $data)
    {
        switch ($driver) {
            case 'local':
                return array_get($data, 'url');
                break;
            case 's3':
                $adapter = File::disk("assets:$id")->filesystem()->getAdapter();
                return rtrim($adapter->getClient()->getObjectUrl($adapter->getBucket(), array_get($data, 'path', '/')), '/');
                break;
        }
    }

    public function isMatchingFile($file)
    {
        return $file['basename'] === 'container.yaml';
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
        dd('asset container locale from path', $path);
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
        dd('asset container locale', $path, $data);
    }
}
