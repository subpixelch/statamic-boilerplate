<?php

namespace Statamic\Assets;

class AssetFactory
{
    protected $data = [];
    protected $uuid;
    protected $locale;
    protected $container;
    protected $folder;
    protected $file;

    /**
     * @param string|null $uuid
     * @return $this
     */
    public function create($uuid = null)
    {
        if ($uuid) {
            $this->uuid($uuid);
        }

        return $this;
    }

    /**
     * @param string $container
     * @return $this
     */
    public function container($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @param string $folder
     * @return $this
     */
    public function folder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @param string $file
     * @return $this
     */
    public function file($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @param $uuid
     * @return $this
     */
    public function uuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function with(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return \Statamic\Assets\Asset
     */
    public function get()
    {
        $asset = new Asset;

        $asset->id($this->uuid);
        $asset->basename($this->file);
        $asset->container($this->container);
        $asset->folder($this->folder);
        $asset->data($this->data);

        $asset->syncOriginal();

        return $asset;
    }
}
