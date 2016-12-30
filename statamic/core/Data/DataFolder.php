<?php

namespace Statamic\Data;

use Statamic\Contracts\Data\DataFolder as DataFolderContract;

abstract class DataFolder implements DataFolderContract
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_get($this->data, $key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get or set the data
     *
     * @param array|null $data
     * @return mixed
     */
    public function data($data = null)
    {
        if (! $data) {
            return $this->data;
        }

        $this->data = $data;
    }

    /**
     * @param string|null $path
     * @return string
     */
    public function path($path = null)
    {
        if (is_null($path)) {
            return $this->path;
        }

        $this->path = $path;
    }

    /**
     * Get the basename of the folder
     *
     * @return string
     */
    public function basename()
    {
        return basename($this->path());
    }

    /**
     * @return string
     */
    public function title()
    {
        return $this->get('title', ucfirst(pathinfo($this->path())['filename']));
    }

}
