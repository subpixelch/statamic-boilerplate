<?php

namespace Statamic\Extend;

use Statamic\API\Path;
use Statamic\Http\Controllers\CpController;

class Controller extends CpController
{
    /**
     * Provides access to addon helper methods
     */
    use Extensible;

    /**
     * Create a new Controller instance
     */
    public function __construct()
    {
        parent::__construct(app('request'));

        $this->init();
    }
}
