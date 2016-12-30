<?php

namespace Statamic\Addons\Grid;

use Statamic\API\Helper;
use Statamic\CP\Fieldset;
use Statamic\Extend\Fieldtype;
use Statamic\CP\FieldtypeFactory;

class GridFieldtype extends Fieldtype
{
    private $process;

    public function canHaveDefault()
    {
        return false;
    }

    public function preProcess($data)
    {
        if (! $data) {
            return [];
        }

        $this->process = 'preProcess';

        return $this->performProcess($data);
    }

    public function process($data)
    {
        $this->process = 'process';

        return $this->performProcess($data);
    }

    private function performProcess($data)
    {
        $processed = [];

        foreach ($data as $i => $row) {
            $processed[$i] = $this->processRow($row);
        }

        return $processed;
    }

    private function processRow($row_data)
    {
        $processed = [];

        foreach ($row_data as $field => $value) {
            $field_config = $this->getFieldConfig('fields.'.$field);

            $processed[$field] = $this->processField($value, $field_config);
        }

        return array_filter($processed);
    }

    private function processField($field_data, $field_config)
    {
        $type = array_get($field_config, 'type', 'text');

        $fieldtype = FieldtypeFactory::create($type, $field_config);

        // Either call $fieldtype->process($data) or $fieldtype->preProcess($data)
        return call_user_func([$fieldtype, $this->process], $field_data);
    }
}
