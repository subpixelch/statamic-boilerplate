<?php

namespace Statamic\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Statamic\Contracts\Extend\Management\AddonManager;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RefreshAddonsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addons:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the installed addons.';

    /**
     * Execute the console command.
     *
     * @param \Statamic\Contracts\Extend\Management\AddonManager $manager
     * @return mixed
     */
    public function handle(AddonManager $manager)
    {
        $packages = $manager->packages();

        if (empty($packages)) {
            $this->info('No addons with dependencies.');
            return;
        }

        $this->line('Adding packages: ' . join(', ', $packages));
        $this->warn('Please wait while dependencies are updated via Composer. This may take a while.');

        try {
            $manager->updateDependencies();
        } catch (ProcessFailedException $e) {
            $this->error($e->getMessage());
        }

        $this->info('Dependencies updated!');
    }
}
