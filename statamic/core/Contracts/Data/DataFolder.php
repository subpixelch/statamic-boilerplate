<?php

namespace Statamic\Contracts\Data;

use Statamic\Contracts\CP\Editable;

interface DataFolder extends Editable
{
    /**
     * Get data from the folder
     *
     * @param string     $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Set a key in the folder's data
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value);

    /**
     * Get or set all of the folder's data
     *
     * @param array|null $data
     * @return mixed
     */
    public function data($data = null);

    /**
     * Get the path of the folder
     *
     * @param string|null $path
     * @return string
     */
    public function path($path = null);

    /**
     * Get the basename of the folder
     *
     * @return string
     */
    public function basename();

    /**
     * Get the title of the folder
     *
     * @return string
     */
    public function title();

    /**
     * Get the last modified date
     *
     * @return \Carbon\Carbon
     */
    public function lastModified();

    /**
     * Save the folder
     *
     * @return mixed
     */
    public function save();

    /**
     * Delete the folder
     *
     * @return mixed
     */
    public function delete();
}
