<?php

namespace Statamic\Addons\Relate;

use Statamic\Addons\Suggest\SuggestFieldtype;

class RelateFieldtype extends SuggestFieldtype
{
    public function preProcess($data)
    {
        $max_items = $this->getFieldConfig('max_items');

        $data = (array) $data;

        if ($max_items === 1) {
            return array_get($data, 0);
        }

        if ($max_items > 1) {
            return array_slice($data, 0, $max_items);
        }

        return $data;
    }

    public function process($data)
    {
        if ($this->getFieldConfig('max_items') === 1 && is_array($data)) {
            return $data[0];
        }

        return $data;
    }
}
