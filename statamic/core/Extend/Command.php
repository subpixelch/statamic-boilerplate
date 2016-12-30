<?php

namespace Statamic\Extend;

use Illuminate\Console\Command as LaravelCommand;

class Command extends LaravelCommand
{
    /**
     * Provides access to addon helper methods
     */
    use Extensible;

    /**
     * Create a new Command instance
     */
    public function __construct()
    {
        parent::__construct();

        $this->init();
    }
}
