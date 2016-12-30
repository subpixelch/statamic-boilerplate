<?php

namespace Statamic\Console\Commands\Generators\Addon;

use Statamic\API\File;
use Statamic\API\Path;
use Statamic\API\YAML;
use Statamic\API\Str;
use Illuminate\Console\Command;

class AddonMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:addon
                            {name? : Name of the addon. If left blank you will be asked.}
                            {--all : Skip the interactive element and just generate everything.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an addon interactively.';

    /**
     * Addon types and their names
     *
     * @var array
     */
    private $types = [
        'API' => 'api',
        'Tags' => 'tags',
        'Filter' => 'filter',
        'Fieldtype' => 'fieldtype',
        'Modifier' => 'modifier',
        'Event Listener' => 'listener',
        'Service Provider' => 'provider',
        'Composer.json' => 'composer',
        'Widget' => 'widget',
        'Controller' => 'controller',
    ];

    /**
     * Whether to generate all the addon aspects
     *
     * @var boolean
     */
    private $all = false;

    /**
     * The name of the addon
     *
     * @var string
     */
    private $addon;

    /**
     * The addon's URL
     *
     * @var string
     */
    private $addon_url;

    /**
     * The vendor/developer name
     *
     * @var string
     */
    private $vendor;

    /**
     * The URL of the vendor/developer
     *
     * @var string
     */
    private $vendor_url;

    /**
     * The version of the addon
     *
     * @var string
     */
    private $version;

    /**
     * A description of the addon
     *
     * @var string
     */
    private $addon_description;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->askQuestions();

        if (! $this->all = $this->option('all')) {
            $this->all = $this->confirm('Should I just generate everything for you?');
        }

        foreach ($this->getSelections() as $type) {
            $args = ['name' => Str::studly($this->addon)];

            if ($type === 'composer') {
                $args['vendor'] = $this->vendor;
            }

            $this->call("make:{$type}", $args);
        }

        $this->generateMeta();

        $this->info("\nYour addon has been created!");
    }

    private function getSelections()
    {
        // Do they want everything? We don't need to ask them.
        if ($this->all) {
            return array_values($this->types);
        }

        $selections = [];

        foreach ($this->types as $name => $type) {
            if ($this->confirm("{$name}?")) {
                $selections[] = $type;
            }
        }

        return $selections;
    }

    private function askQuestions()
    {
        $this->addon = ($this->argument('name'))
            ? $this->argument('name')
            : $this->ask('What is the name of your addon?');

        $this->line('Great name!');

        $this->vendor = $this->ask("What's the developer name?");
        $this->vendor_url = $this->ask("What's the developer URL?", '');
        $this->version = $this->ask("What version is your addon?", '1.0');
        $this->addon_url = $this->ask("What's the URL of your addon? eg. For marketing or documentation.", '');
        $this->addon_description = $this->ask("What does your addon do, in one sentence?", '');

        $this->line('Oooh! I wish I thought of that.');
    }

    private function generateMeta()
    {
        $meta = [
            'name' => $this->addon,
            'version' => $this->version,
            'description' => $this->addon_description,
            'url' => $this->addon_url,
            'developer' => $this->vendor,
            'developer_url' => $this->vendor_url
        ];

        $path = addons_path(Str::studly($this->addon) . '/meta.yaml');

        File::put($path, YAML::dump($meta));

        $this->info('Your Meta file awaits at: '.Path::makeRelative($path));
    }
}
