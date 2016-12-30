<?php

namespace Statamic\Extend;

use Statamic\API\Path;
use Statamic\API\Helper;

class Widget
{
    /**
     * Provides access to addon helper methods
     */
    use Extensible;

    /**
     * The widget configuration as supplied by the user
     * @var array
     */
    protected $config;

    /**
     * Create a new Widget instance
     *
     * @param array $config Field configuration
     */
    public function __construct($config)
    {
        $this->bootstrap();
        $this->init();

        $this->config = $config;
    }

    /**
     * Get the widget config
     *
     * @param string|null $key
     * @param string|null $default
     * @return mixed
     */
    public function getConfig($key = null, $default = null)
    {
        if (! $key) {
            return $this->config;
        }

        return array_get($this->config, $key, $default);
    }

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
            $this->getConfig($keys, $default),
            $default
        );
    }

    /**
     * Same as $this->get(), but treats as a boolean
     *
     * @param string|array $keys
     * @param null         $default
     * @return bool
     */
    protected function getBool($keys, $default = null)
    {
        return bool($this->get($keys, $default));
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
}
