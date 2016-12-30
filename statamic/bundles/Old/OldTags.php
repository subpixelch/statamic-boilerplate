<?php

namespace Statamic\Addons\Old;

use Statamic\API\Str;
use Statamic\Extend\Tags;

class OldTags extends Tags
{
    public function __call($method, $args)
    {
        $var = Str::removeLeft($this->tag, 'old:');

        // Laravel's old() helper accepts dot notation. We want to allow colon notation.
        $var = str_replace(':', '.', $var);

        // Get the old value, but if there's nothing we can bail out here.
        if (! $value = old($var)) {
            return;
        }

        // If it's a tag pair, we'll wangjangle the output a little to make it easier for the developer.
        if ($this->isPair) {
            $this->content = '{{'.$var.'}}' . $this->content . '{{/'.$var.'}}';
            return $this->parse([$var => $value]);
        }

        return $value;
    }
}
