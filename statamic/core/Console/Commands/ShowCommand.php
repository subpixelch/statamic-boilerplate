<?php

namespace Statamic\Console\Commands;

use Statamic\API\Pattern;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

class ShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shows Statamic commands';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $commands = $this->getCommands();

        $headers = ['Command', 'Description'];
        $rows = [];

        foreach ($commands as $command) {
            $rows[] = [$command->getName(), $command->getDescription()];
        }

        $this->comment("\nType `php please <command> --help` for more information about that command.\n");

        $this->table($headers, $rows);
    }

    /**
     * Get all the Statamic commands
     *
     * @return array
     */
    private function getCommands()
    {
        $commands = new Collection(Artisan::all());

        $commands = $commands->filter(function($command) {
            return Pattern::startsWith(get_class($command), 'Statamic');
        })->sortBy(function($command) {
            return $command->getName();
        });

        $namespaced_commands = $commands->reject(function($command) {
            return str_contains($command->getName(), ':');
        });

        $root_commands = $commands->filter(function($command) {
            return str_contains($command->getName(), ':');
        });

        return $namespaced_commands->merge($root_commands);
    }
}
