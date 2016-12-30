<?php

namespace Statamic\Extend;

use Statamic\API\Helper;

trait HasParameters
{
    /**
     * An array of parameters set on this tag
     * @public array
     */
    public $parameters;

    /**
     * Retrieves a parameter or config value
     *
     * @param string|array $keys Keys of parameter to return
     * @param null         $default
     * @return mixed
     */
    protected function get($keys, $default = null)
    {
        return Helper::pick(
            $this->getParam($keys),
            $this->getConfig($keys),
            $default
        );
    }

    /**
     * Same as $this->get(), but treats as a boolean
     *
     * @param string|array $keys
     * @param false         $default
     * @return bool
     */
    protected function getBool($keys, $default = false)
    {
        return bool($this->get($keys, $default));
    }

    /**
     * Same as $this->get(), but treats as a float
     *
     * @param string|array $keys
     * @param null         $default
     * @return float
     */
    protected function getFloat($keys, $default = null)
    {
        return (float) $this->get($keys, $default);
    }

    /**
     * Same as $this->get(), but treats as an integer
     *
     * @param string|array $keys
     * @param null         $default
     * @return int
     */
    protected function getInt($keys, $default = null)
    {
        return int($this->get($keys, $default));
    }

    /**
     * Retrieves a parameter
     *
     * @param string|array $keys Keys of parameter to return
     * @param mixed $default  Default value to return if not set
     * @return mixed
     */
    protected function getParam($keys, $default = null)
    {
        if (! is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            if (isset($this->parameters[$key])) {
                return $this->parameters[$key];
            }
        }

        return $default;
    }

    /**
     * Same as $this->getParam(), but treats as a boolean
     *
     * @param string|array $keys
     * @param null         $default
     * @return bool
     */
    protected function getParamBool($keys, $default = null)
    {
        return bool($this->getParam($keys, $default));
    }

    /**
     * Same as $this->getParam(), but treats as an integer
     *
     * @param string|array $keys
     * @param null         $default
     * @return int
     */
    protected function getParamInt($keys, $default = null)
    {
        return int($this->getParam($keys, $default));
    }

    /**
     * Retrieves a parameters and explodes any | delimiters
     *
     * @param string|array $keys
     * @param null         $default
     * @return int
     */
    protected function getList($keys, $default = null)
    {
        $keys = $this->getParam($keys, $default);

        return ($keys) ? explode('|', $keys) : $default;
    }
}