<?php

namespace Statamic\Stache\Drivers;

use Statamic\API\Asset;
use Statamic\API\YAML;
use Statamic\API\Folder;

class AssetsDriver extends AbstractDriver implements AggregateDriver
{
    protected $routable = true;

    protected $multi_item = true;

    public function getFilesystemDriver()
    {
        return Folder::disk('storage')->filesystem()->getDriver();
    }

    public function getFilesystemRoot()
    {
        return 'assets';
    }

    public function getRepoCacheKey()
    {
        return 'assets/' . $this->key() . '/items';
    }

    public function getModifiedItems($files)
    {
        $creator = new AssetItemCreator(
            $this->stache,
            $files
        );

        return $creator->create();
    }

    public function createItem($path, $contents)
    {
        //
    }

    /**
     * Delete the items from the repo
     *
     * @param \Statamic\Stache\Repository $repo
     * @param \Illuminate\Support\Collection $deleted
     * @param \Illuminate\Support\Collection $modified
     */
    public function deleteItems($repo, $deleted, $modified)
    {
        // Assets can be removed from a folder.yaml without the folder.yaml being
        // completed deleted. This will be treated as a modified file. We'll get
        // all the removed assets and remove them from the Stache repo.
        $modified->each(function ($contents, $path) use ($repo) {
            $data = YAML::parse($contents);
            $assets = array_get($data, 'assets', []);
            $key = $this->getKeyFromPath($path);
            $repo_ids = $repo->repo($key)->getIds()->all();
            $deleted_ids = collect(array_diff_key($repo_ids, $assets));

            foreach ($deleted_ids as $id) {
                $repo->removeItem("$key::$id");
            }
        });

        // Next, any actual deleted folder.yaml will need to have all their
        // assets removed. We can simply just delete the sub-repo.
        $deleted->each(function ($path) use ($repo) {
            $key = $this->getKeyFromPath($path);
            $repo->removeRepo($key);
        });
    }

    public function isMatchingFile($file)
    {
        return $file['basename'] === 'folder.yaml';
    }

    public function toPersistentArray($repo)
    {
        return [
            'meta' => [
                'paths' => $this->getPersistentPaths($repo),
                'uris' => $this->getPersistentUris($repo),
            ],
            'items' => $this->getPersistentItems($repo)
        ];
    }

    private function getPersistentPaths($repo)
    {
        $paths = [];

        foreach ($repo->getPaths() as $key => $collection) {
            foreach ($collection as $id => $path) {
                $paths[$key.'::'.$id] = $path;
            }
        }

        return $paths;
    }

    private function getPersistentUris($repo)
    {
        $urls = [];

        foreach ($repo->getUris() as $key => $collection) {
            foreach ($collection as $id => $url) {
                $urls[$key.'::'.$id] = $url;
            }
        }

        return $urls;
    }

    private function getPersistentItems($repo)
    {
        $items = [];

        foreach ($repo->getItems() as $key => $collection) {
            $items[$key.'/data'] = $collection->map(function ($asset) {
                return $asset->shrinkWrap();
            })->all();
        }

        return $items;
    }

    /**
     * Get the locale based on the path
     *
     * @param string $path
     * @return string
     */
    public function getLocaleFromPath($path)
    {
        dd('asset locale from path', $path);
    }

    /**
     * Get the key from a path
     *
     * @param string $path
     * @return string
     */
    public function getKeyFromPath($path)
    {
        // Remove `assets/` and `/folder.yaml`
        return substr($path, 7, -12);
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
        dd('asset locale', $path, $data);
    }

    /**
     * @inheritdoc
     */
    public function load($collection)
    {
        return $collection->map(function ($item, $id) {
            $attr = $item['attributes'];

            return Asset::create($id)
                ->container($attr['container'])
                ->folder($attr['folder'])
                ->file($attr['basename'])
                ->with($item['data'][default_locale()])
                ->get();
        });
    }
}
