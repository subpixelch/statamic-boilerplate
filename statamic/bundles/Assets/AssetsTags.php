<?php

namespace Statamic\Addons\Assets;

use Statamic\API\Asset;
use Statamic\API\AssetContainer;
use Statamic\API\Helper;
use Statamic\Extend\Tags;
use Statamic\Assets\AssetCollection;

class AssetsTags extends Tags
{
    /**
     * @var AssetCollection
     */
    private $assets;

    /**
     * Iterate over multiple Assets' data from a value
     *
     * Usage:
     * {{ asset:[variable] }}
     *   {{ url }}, etc
     * {{ /asset:[variable] }}
     *
     * @param $method
     * @param $arguments
     * @return string
     */
    public function __call($method, $arguments)
    {
        $value = array_get($this->context, explode(':', $this->tag)[1]);

        return $this->assets($value);
    }

    /**
     * Iterate over all assets in a container and optionally by folder
     *
     * Usage:
     * {{ assets path="assets" }}
     *   {{ url }}, etc
     * {{ /assets }}
     *
     * @return string
     */
    public function index()
    {
        $id = $this->get(['container', 'id']);
        $path = $this->get('path');

        if (!$id && !$path) {
            \Log::debug('No asset container ID or path was specified.');
            return;
        }

        // Get the assets (container) by either ID or path.
        $container = ($id) ? AssetContainer::find($id) : AssetContainer::wherePath($path);

        // Optionally target a folder
        if ($folder = $this->get('folder')) {
            $container = $container->folder($folder);
        }

        $this->assets = $container->assets();

        return $this->output();
    }

    /**
     * Perform the asset lookups
     *
     * @param string|array $ids  One ID, or array of IDs.
     * @return string
     */
    protected function assets($ids)
    {
        if (! $ids) {
            return;
        }

        $ids = Helper::ensureArray($ids);

        $this->assets = collect_assets();

        foreach ($ids as $id) {
            if ($asset = Asset::find($id)) {
                $this->assets->put($asset->id(), $asset);
            }
        }

        return $this->output();
    }

    private function output()
    {
        $this->sort();
        $this->limit();

        return $this->parseLoop($this->assets);
    }

    private function sort()
    {
        if ($sort = $this->get('sort')) {
            $this->assets = $this->assets->multisort($sort);
        }
    }

    /**
     * Limit and offset the asset collection
     *
     * @return array
     */
    private function limit()
    {
        $limit = $this->getInt('limit');
        $limit = ($limit == 0) ? $this->assets->count() : $limit;
        $offset = $this->getInt('offset');

        $this->assets = $this->assets->splice($offset, $limit);
    }
}
