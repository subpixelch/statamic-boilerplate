<?php

namespace Statamic\Extend;

use Statamic\API\Fieldset;
use Statamic\API\Path;
use Statamic\API\Helper;
use Statamic\API\Str;

/**
 * Control panel fieldtype
 */
class Fieldtype implements FieldtypeInterface
{
    /**
     * Provides access to addon helper methods
     */
    use Extensible;

    /**
     * The configuration of the field from within a fieldset
     * @var array
     */
    protected $field_config;

    /**
     * The data contained in this field
     * @var mixed
     */
    protected $field_data;

    /**
     * The name of this fieldtype in snake case format, if desired.
     * @var string
     */
    protected $snake_name;

    /**
     * Whether this is a config field
     * @var bool
     */
    public $is_config = false;

    /**
     * Create a new fieldtype instance
     *
     * @param array $config Field configuration
     */
    public function __construct($config)
    {
        $this->bootstrap();
        $this->init();

        $this->field_config = $config;
    }

    /**
     * Get the field config
     *
     * @param string|null $key
     * @param string|null $default
     * @return mixed
     */
    public function getFieldConfig($key = null, $default = null)
    {
        if (! $key) {
            return $this->field_config;
        }

        return array_get($this->field_config, $key, $default);
    }

    /**
     * Gets the field's name
     *
     * @return mixed
     */
    public function getName()
    {
        return array_get($this->field_config, 'name');
    }

    public function snakeName()
    {
        return $this->snake_name ?: Str::snake($this->getAddonClassName());
    }

    /**
     * Returns the field's HTML input name
     *
     * @return string
     */
    public function getInputName()
    {
        return 'fields[' . $this->getName() . ']';
    }

    /**
     * Get the field's data
     *
     * @param mixed $default
     * @return mixed
     */
    public function getData($default = null)
    {
        $data = $this->field_data ?: $default;

        return $this->preProcess($data);
    }

    /**
     * Set the field's data
     *
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->field_data = $data;
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
            $this->getParam($keys, $default),
            $default
        );
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
            if (isset($this->field_config[$key])) {
                return $this->field_config[$key];
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
     * Allows processing of the data before being used
     *
     * @param mixed $data  Data from the content
     * @return mixed
     */
    public function preProcess($data)
    {
        return $data;
    }

    /**
     * Allows processing of the data upon saving
     *
     * @param mixed $data  Data from the publish page form
     * @return mixed
     */
    public function process($data)
    {
        return $data;
    }

    /**
     * The fieldtype's default/blank value
     *
     * @return null
     */
    public function blank()
    {
        return null;
    }

    /**
     * Validation rules
     *
     * @return null|string
     */
    public function rules()
    {
        return null;
    }

    public function getConfigFieldset()
    {
        $fields = array_get($this->getMeta(), 'fieldtype_fields', []);

        $fieldset = Fieldset::create('config', compact('fields'));
        $fieldset->type('fieldtype');

        return $fieldset;
    }

    /**
     * Can this field have validation rules?
     *
     * @return bool
     */
    public function canBeValidated()
    {
        return true;
    }

    /**
     * Can this field be localized?
     *
     * @return bool
     */
    public function canBeLocalized()
    {
        return true;
    }

    /**
     * Can this field have a default value?
     *
     * @return bool
     */
    public function canHaveDefault()
    {
        return true;
    }
}
