<?php

namespace Statamic\Assets;

use Statamic\Data\DataCollection;

class AssetCollection extends DataCollection
{
    public function toArray()
    {
        // Remove any assets that aren't actually there.
        // @todo Evaluate whether this is a good solution. It involves checking files every time.
        foreach ($this->items as $key => $asset) {
            if (! $asset->disk()->exists($asset->path())) {
                $this->forget($key);
            }
        }

        return parent::toArray();
    }
}
