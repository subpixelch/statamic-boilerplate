<?php

namespace Statamic\Providers;

use Statamic\API\Helper;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'content.saved' => [
            \Statamic\StaticCaching\Invalidator::class
        ],
        \Statamic\Events\DataIdCreated::class => [
            \Statamic\Stache\Listeners\SaveCreatedId::class
        ],
        \Statamic\Events\SearchSettingsUpdated::class => [
            \Statamic\Listeners\UpdateSearchIndex::class,
            \Statamic\Listeners\FlushCache::class,
        ],
    ];

    protected $subscribe = [
        \Statamic\Stache\Listeners\UpdateItem::class
    ];

    public function register()
    {
        //
    }

    public function boot(DispatcherContract $dispatcher)
    {
        parent::boot($dispatcher);

        if (refreshing_addons()) {
            return;
        }

        // We only care about the listener classes
        $listeners = app('Statamic\Repositories\AddonRepository')->filter('Listener.php')->getClasses();

        // Register all the events specified in each listener class
        foreach ($listeners as $class) {
            $listener = app($class);

            foreach ($listener->events as $event => $methods) {
                foreach (Helper::ensureArray($methods) as $method) {
                    $dispatcher->listen($event, [$listener, $method]);
                }
            }
        }
    }
}
