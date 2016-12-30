<?php

namespace Statamic\Addons\Suggest\Modes;

use Statamic\API\Parse;
use Statamic\API\Str;
use Illuminate\Http\Request;
use Statamic\Addons\Suggest\Mode;

abstract class AbstractMode implements Mode
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function label($object, $default)
    {
        $label = $this->request->input('label', $default);

        // Placeholders have been specified, we'll need to parse the label.
        if (Str::contains($label, '{')) {
            return Parse::template($label, $object->toArray());
        }

        return method_exists($object, $label)
            ? $object->$label()
            : $object->get($label);
    }
}
