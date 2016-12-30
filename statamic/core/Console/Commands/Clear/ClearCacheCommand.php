<?php

namespace Statamic\Console\Commands\Clear;

use Illuminate\Console\Command;

class ClearCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'clear:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the application cache.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->call('cache:clear');
    }
}
