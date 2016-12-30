<?php

namespace Statamic\Stache\Drivers;

use Illuminate\Support\Collection;
use Statamic\API\Asset;
use Statamic\API\Path;
use Statamic\API\Str;
use Statamic\API\YAML;
use Statamic\Stache\Stache;

class AssetItemCreator
{
    /**
     * @var \Statamic\Stache\Stache
     */
    private $stache;

    /**
     * @var Collection
     */
    private $files;

    /**
     * @var string
     */
    protected $container;

    /**
     * @var Collection
     */
    private $items;

    public function __construct(Stache $stache, $files)
    {
        $this->stache = $stache;
        $this->files = $files->first(); // default locale only
        $this->items = collect();
    }

    /**
     * Create/get all the assets sorted into `container.folder` keys
     *
     * @return Collection
     */
    public function create()
    {
        return $this->getContainers()->flatMap(function ($folders, $id) {
            return $this->createFolders($id, $folders);
        })->map(function ($items, $folder) {
            return compact('items', 'folder');
        })->map(function ($item) {
            $item['key'] = substr($item['folder'], 7, -12);
            return $item;
        })->pluck('items', 'key');
    }

    private function getContainers()
    {
        return $this->files->groupBy(function ($contents, $path) {
            return explode('/', $path)[1];
        }, true);
    }

    private function createFolders($container, $folders)
    {
        return $folders->map(function ($contents) {
            return array_get(YAML::parse($contents), 'assets', []);
        })->map(function ($assets, $folder) use ($container) {
            $folder = $this->getFolderFromPath($folder);
            $path = $this->stache->repo('assetfolders')->getItem(
                'assets/' . rtrim(Path::assemble($container, $folder), '/')
            )->path();
            return $this->createAssets($container, $folder, $path, $assets);
        });
    }

    private function createAssets($container, $folder, $folder_path, $assets)
    {
        return collect($assets)->map(function ($data, $id) use ($container, $folder, $folder_path) {
            $file = $data['file'];
            unset($data['file']);

            $asset = Asset::create($id)
                ->file($file)
                ->container($container)
                ->folder($folder)
                ->with($data)
                ->get();

            $asset->path(ltrim(str_replace('//', '/', $folder_path.'/'.$file), '/'));

            return [
                'item' => $asset,
                'path' => str_replace('//', '', $folder.'/'.$file)
            ];
        });
    }

    private function getFolderFromPath($path)
    {
        $parts = explode('/', $path);

        $folder = implode('/', array_slice($parts, 2, -1));

        $folder = ($folder === '') ? '/' : $folder;

        return $folder;
    }
}