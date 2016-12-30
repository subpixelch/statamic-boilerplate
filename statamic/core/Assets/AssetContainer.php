<?php

namespace Statamic\Assets;

use Statamic\API\Asset as AssetAPI;
use Statamic\API\Folder;
use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\Fieldset;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Parse;
use Statamic\API\Storage;
use Statamic\Data\Services\AssetFoldersService;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Filesystem\FolderAccessor;

class AssetContainer implements AssetContainerContract
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $driver = 'local';

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var array
     */
    protected $folders = [];

    /**
     * @var string
     */
    protected $fieldset;

    /**
     * Get or set the ID
     *
     * @param null|string $id
     * @return string
     */
    public function id($id = null)
    {
        if (is_null($id)) {
            return $this->uuid;
        }

        return $this->uuid = $id;
    }

    public function uuid($uuid = null)
    {
        return $this->id($uuid);
    }

    /**
     * Get or set the driver
     *
     * @param  null|string $driver
     * @return string
     */
    public function driver($driver = null)
    {
        if (is_null($driver)) {
            return $this->driver;
        }

        return $this->driver = $driver;
    }

    public function data($data = null)
    {
        if (! is_null($data)) {
            $this->data = $data;
            return;
        }

        if ($this->data) {
            return $this->data;
        }

        $path = 'assets/' . $this->uuid . '/container.yaml';

        $this->data = YAML::parse(Storage::get($path));

        return $this->data;
    }

    /**
     * Get or set the title
     *
     * @param null|string $title
     * @return string
     */
    public function title($title = null)
    {
        if ($title) {
            $this->title = $title;
        }

        return $this->title;
    }

    /**
     * Get or set the path
     *
     * @param null|string $path
     * @return string
     */
    public function path($path = null)
    {
        if ($path) {
            $this->path = $path;
        }

        return $this->path;
    }

    /**
     * Get the full resolved path
     *
     * @return string
     */
    public function resolvedPath()
    {
        return Parse::env($this->path());
    }

    /**
     * Get or set the URL to this location
     *
     * @param string|null $url
     * @return null|string
     */
    public function url($url = null)
    {
        if (! is_null($url)) {
            $this->url = $url;
        }

        return $this->url;
    }

    /**
     * Get all the assets in this container
     *
     * @return \Statamic\Assets\AssetCollection
     */
    public function assets()
    {
        $assets = [];

        foreach ($this->folders() as $folder) {
            foreach ($folder->assets() as $uuid => $asset) {
                $assets[$uuid] = $asset;
            }
        }

        return new AssetCollection($assets);
    }

    /**
     * Get all the folders in this container
     *
     * @return Collection
     */
    public function folders()
    {
        // We would use ->filter() here but it doesn't pass in keys in Laravel 5.1
        // For now we'll return nulls for any folders that should be filtered out.
        return app(AssetFoldersService::class)->all()->map(function ($folder, $key) {
            if (Str::startsWith($key.'/', 'assets/'.$this->id().'/')) {
                return $folder;
            }
        })->filter()->keyBy(function ($folder) {
            return $folder->path();
        });
    }

    /**
     * Get a single folder in this container
     *
     * @param string $folder
     * @return \Statamic\Contracts\Assets\AssetFolder
     */
    public function folder($folder)
    {
        return $this->folders()->get($folder);
    }

    /**
     * Check if a folder exists
     *
     * @param string $folder
     * @return bool
     */
    public function folderExists($folder)
    {
        return $this->folders()->has($folder);
    }

    /**
     * Create a folder
     *
     * @param string $folder
     * @param array  $data
     * @return AssetFolder
     */
    public function createFolder($folder, $data = [])
    {
        $folder = new AssetFolder($this->uuid(), $folder, $data);

        $folder->save();

        return $folder;
    }

    /**
     * Add a folder to this container
     *
     * @param string                                 $name
     * @param \Statamic\Contracts\Assets\AssetFolder $folder
     */
    public function addFolder($name, $folder)
    {
        $this->folders[$name] = $folder;
    }

    /**
     * Remove a folder from this container
     *
     * @param string $name
     */
    public function removeFolder($name)
    {
        unset($this->folders[$name]);
    }

    /**
     * Convert to an array
     *
     * @return array
     */
    public function toArray()
    {
        $data = $this->data();

        $data['id'] = $this->uuid();

        return $data;
    }

    /**
     * Get the URL to edit in the CP
     *
     * @return string
     */
    public function editUrl()
    {
        return cp_route('assets.container.edit', $this->uuid());
    }

    /**
     * Get or set the fieldset to be used by assets in this container
     *
     * @param string $fieldset
     * @return Statamic\Contracts\CP\Fieldset
     */
    public function fieldset($fieldset = null)
    {
        if (is_null($fieldset)) {
            return ($this->fieldset) ? Fieldset::get($this->fieldset) : null;
        }

        if ($fieldset === false) {
            return $this->fieldset = null;
        }

        $this->fieldset = $fieldset;
    }

    /**
     * Sync any new files into assets.
     *
     * @return mixed
     */
    public function sync()
    {
        $disk = Folder::disk('assets:' . $this->uuid());

        $this->syncFolders($disk);

        return $this->syncFiles($disk);
    }

    private function syncFolders(FolderAccessor $disk)
    {
        $folders = $disk->getFoldersRecursively('/');

        foreach ($folders as $folder) {
            if ($this->folderExists($folder)) {
                continue;
            }

            $this->addFolder($folder, $this->createFolder($folder));
        }
    }

    private function syncFiles(FolderAccessor $disk)
    {
        $files = $disk->getFilesRecursively('/');

        $assets = [];

        foreach ($files as $path) {
            // Always ignore some files.
            if (in_array(pathinfo($path)['basename'], ['.DS_Store'])) {
                continue;
            }

            if ($this->assetExists($path)) {
                continue;
            }

            $assets[] = $this->createAsset($path);
        }

        return new AssetCollection($assets);
    }

    /**
     * Check if an asset with a given path exists in this container
     *
     * @param string $path
     * @return bool
     */
    public function assetExists($path)
    {
        foreach ($this->assets() as $asset) {
            if (ltrim($asset->path(), '/') === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create an asset in this container
     *
     * @param string $path
     * @return Asset
     */
    public function createAsset($path)
    {
        $pathinfo = pathinfo($path);

        $folder = $pathinfo['dirname'];
        $folder = ($folder === '.') ? '/' : $folder;

        $asset = AssetAPI::create()
                      ->container($this->uuid())
                      ->folder($folder)
                      ->file($pathinfo['basename'])
                      ->get();

        $asset->save();

        return $asset;
    }

    /**
     * Get or set the handle
     *
     * @param null|string $handle
     * @return string
     */
    public function handle($handle = null)
    {
        // For files, the id is also the handle.
        return $this->id($handle);
    }

    /**
     * Save the container
     */
    public function save()
    {
        $path = 'assets/' . $this->uuid . '/container.yaml';

        $data = array_filter($this->toArray());
        unset($data['id']);
        $yaml = YAML::dump($data);

        Storage::put($path, $yaml);

        // Create an empty folder if one doesn't exist.
        $folder_path = 'assets/' . $this->uuid . '/folder.yaml';
        if (! Storage::exists($folder_path)) {
            $yaml = YAML::dump([
                'title' => $this->title,
                'assets' => []
            ]);

            Storage::put($folder_path, $yaml);
        }
    }

    /**
     * Delete the container
     *
     * @return mixed
     */
    public function delete()
    {
        Folder::disk('storage')->delete('assets/' . $this->uuid);
    }
}
