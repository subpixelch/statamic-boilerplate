<?php

namespace Statamic\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
       'Statamic\Console\Commands\ShowCommand',
       'Statamic\Console\Commands\JokeCommand',
       'Statamic\Console\Commands\GlobetrottersCommand',
       'Statamic\Console\Commands\Assets\AssetsListCommand',
       'Statamic\Console\Commands\Assets\AssetsFindCommand',
       'Statamic\Console\Commands\Assets\AssetsSyncCommand',
       'Statamic\Console\Commands\Clear\ClearCacheCommand',
       'Statamic\Console\Commands\Clear\ClearStacheCommand',
       'Statamic\Console\Commands\Clear\ClearGlideCommand',
       'Statamic\Console\Commands\Clear\ClearSiteCommand',
       'Statamic\Console\Commands\Generators\Theme\ThemeMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\AddonMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\ListenerMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\ApiMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\TagsMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\TasksMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\FieldtypeMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\FilterMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\CommandMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\ModifierMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\ProviderMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\ComposerMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\WidgetMakeCommand',
       'Statamic\Console\Commands\Generators\Addon\ControllerMakeCommand',
       'Statamic\Console\Commands\Generators\UserMakeCommand',
       'Statamic\Console\Commands\Generators\FieldsetMakeCommand',
       'Statamic\Console\Commands\RefreshAddonsCommand',
       'Statamic\Console\Commands\Config\ConfigSetCommand',
       'Statamic\Console\Commands\Convert\ConvertEmailLoginCommand',
       'Statamic\Console\Commands\SetCommand',
    ];

    /**
     * @var array
     */
    protected $addon_commands = [];

    /**
     * Override the bootstrapper
     */
    public function bootstrap()
    {
        parent::bootstrap();

        $this->registerAddonCommands();

        $this->getArtisan()->setDefaultCommand('show');
    }

    public function bootstrappers()
    {
        array_splice($this->bootstrappers, 2, 0, [
            'Statamic\Bootstrap\UpdateConfiguration'
        ]);

        return $this->bootstrappers;
    }

    /**
     * Register any commands in addons
     */
    private function registerAddonCommands()
    {
        $commands = $this->getCommands();

        $this->getArtisan()->addCommands($commands);
    }

    /**
     * Gather all the addon's commands from the filesystem
     *
     * @return \Symfony\Component\Console\Command\Command[]
     */
    private function getCommands()
    {
        $classes = addon_repo()->filter('Command.php')->getClasses();

        foreach ($classes as $class) {
            $this->addon_commands[] = new $class;
        }

        return $this->addon_commands;
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $tasks = addon_repo()->filter('Tasks.php')->getClasses();

        // In each addon's task class, we'll pass along the scheduler
        // instance and let the class define its own schedule.
        foreach ($tasks as $class) {
            (new $class)->schedule($schedule);
        }
    }
}
