<?php

namespace Statamic\Addons\Yaml;

use Statamic\API\YAML;
use Statamic\Extend\Fieldtype;

class YamlFieldtype extends Fieldtype
{
    public function preProcess($data)
    {
        // When it's a config value being processed, we want to leave it as an array.
        // For example, the `settings` config in the Redactor fieldtype should be an
        // array when being passed into the fieldtype, but should be converted to a
        // string when inside the fieldset builder and we're editing the actual YAML.
        return ($this->is_config) ? $data : YAML::dump($data);
    }

    public function process($data)
    {
        return YAML::parse($data);
    }
}
