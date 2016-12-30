<?php

namespace Statamic\Extend;

interface FilterInterface
{
    /**
     * @return \Statamic\Data\DataCollection
     */
    public function filter();
}
