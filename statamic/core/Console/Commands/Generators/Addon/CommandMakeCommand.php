<?php

namespace Statamic\Console\Commands\Generators\Addon;

class CommandMakeCommand extends GeneratorCommand
{
    /**
     * The type of addon class being generated.
     *
     * @var string
     */
    protected $type = 'command';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:command {name : Name of your addon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an addon command file.';
}
