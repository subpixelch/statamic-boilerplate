<?php

namespace Statamic\Extend\Management;

use Statamic\API\Folder;
use Statamic\API\Pattern;
use Statamic\API\Str;
use Statamic\Exceptions\ResourceNotFoundException;

/**
 * Addon resource management
 */
class Loader
{
    /**
     * Types of addon resource types
     * @var array
     */
    public $resource_types = ['Tags', 'Modifier', 'Fieldtype', 'Listener', 'Widget'];

    /**
     * Load an addon's Tags class
     *
     * @param string $name       Name of the addon
     * @param array  $properties Properties the tag would use from the Parser
     * @return \Statamic\Extend\Tags
     * @throws \Exception
     * @throws \Statamic\Exceptions\ResourceNotFoundException
     */
    public function loadTags($name, $properties)
    {
        $name = $this->getTagAlias($name);

        return $this->loadAddonResource('Tags', $name, $properties);
    }

    /**
     * Load an addon's Widgets class
     *
     * @param string $name       Name of the addon
     * @param array  $properties Properties the tag would use from the Parser
     * @return \Statamic\Extend\Tags
     * @throws \Exception
     * @throws \Statamic\Exceptions\ResourceNotFoundException
     */
    public function loadWidget($name, $config = [])
    {
        return $this->loadAddonResource('Widget', $name, $config);
    }

    /**
     * Load all the widgets
     *
     * @param array $list List of widgets and their configs
     * @return array of \Statamic\Extend\Widget
     * @throws \Exception
     * @throws \Statamic\Exceptions\ResourceNotFoundException
     */
    public function loadWidgets($list)
    {
        $widgets = [];

        foreach ($list as $key => $widget) {
            $name = array_get($widget, 'type');
            $widgets[] = $this->loadWidget($name, $widget);
        }

        return $widgets;
    }

    /**
     * Load an addon's Modifier class
     *
     * @param string $name  Name of the addon
     * @return \Statamic\Extend\Tags
     * @throws \Exception
     * @throws \Statamic\Exceptions\ResourceNotFoundException
     */
    public function loadModifier($name)
    {
        return $this->loadAddonResource('Modifier', $name);
    }

    /**
     * Load an addon's Fieldtype class
     *
     * @param string $name    Name of the addon
     * @param array  $config  Fieldset field config
     * @return \Statamic\Extend\Fieldtype
     * @throws \Exception
     * @throws \Statamic\Exceptions\ResourceNotFoundException
     */
    public function loadFieldtype($name, $config = [])
    {
        $name = $this->getFieldtypeAlias($name);

        return $this->loadAddonResource('Fieldtype', $name, $config);
    }

    /**
     * Register event listeners for addons that contain them
     */
    public static function registerListeners()
    {
        foreach ([bundles_path(), addons_path()] as $folder) {
            foreach (Folder::getFilePathsRecursively($folder) as $path) {
                if (! Pattern::endsWith($path, 'Listener.php')) {
                    continue;
                }

                $class = str_replace('Listener', '', basename($path, '.php'));
                $class = "\\Statamic\\Addons\\{$class}\\{$class}Listener";

                (new $class)->registerEvents();
            }
        }
    }

    /**
     * Load an addon's resource
     *
     * @param string     $type   Type of resource. eg. Tags, etc
     * @param string     $name   Name of the addon
     * @param null|array $extra  Any extra data the resource class might need
     * @return mixed             An instance of a Plugin class. \Plugin\Tags, etc.
     * @throws \Exception
     * @throws \Statamic\Exceptions\ResourceNotFoundException
     */
    public function loadAddonResource($type, $name, $extra = null)
    {
        if (! in_array($type, $this->resource_types)) {
            throw new \Exception("Unknown resource type: {$type}");
        }

        $studly_name = Str::studly($name);

        $class = "\\Statamic\\Addons\\{$studly_name}\\{$studly_name}{$type}";

        if (class_exists($class)) {
            return new $class($extra);
        }

        $type = rtrim(strtolower($type), 's');
        throw new ResourceNotFoundException("Could not find files to load the `{$name}` {$type}.");
    }

    /**
     * Parse for tag aliases
     *
     * @param string $original_tag Original tag to check for
     * @return string
     */
    private function getTagAlias($original_tag)
    {
        switch ($original_tag) {
            case "switch":
                return "rotate";

            case 'page':
            case 'entry':
                return 'Crud';

            case '404':
                return 'NotFound';

            case 'yield':
                return 'Yields';

            // temporary until we add aliasing for addons
            case 'var':
                return 'Variables';

            default:
                return $original_tag;
        }
    }

    /**
     * Parse for fieldtype aliases
     *
     * @param string $original_fieldtype Original fieldtype to check for
     * @return string
     */
    private function getFieldtypeAlias($original_fieldtype)
    {
        switch ($original_fieldtype) {
            case "list":
                return "lists";

            case 'array':
                return 'arr';

            default:
                return $original_fieldtype;
        }
    }
}
