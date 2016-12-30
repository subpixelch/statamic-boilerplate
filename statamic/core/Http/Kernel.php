<?php

namespace Statamic\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
        'Statamic\StaticCaching\Middleware\Retrieve',
        'Statamic\Http\Middleware\Glide',
        'Illuminate\Cookie\Middleware\EncryptCookies',
        'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
        'Illuminate\Session\Middleware\StartSession',
        'Illuminate\View\Middleware\ShareErrorsFromSession',
        'Statamic\Http\Middleware\VerifyCsrfToken',
        'Statamic\Http\Middleware\PersistStache',
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => 'Statamic\Http\Middleware\CP\Authenticate',
        'start' => 'Statamic\Http\Middleware\CP\StartPage',
        'configurable' => 'Statamic\Http\Middleware\CP\Configurable',
        'installer' => 'Statamic\Http\Middleware\Installer',
        'outpost' => 'Statamic\Http\Middleware\Outpost',
        'locale' => 'Statamic\Http\Middleware\CP\DefaultLocale',
        'staticcache' => 'Statamic\StaticCaching\Middleware\Cache',
    ];

    public function bootstrappers()
    {
        array_splice($this->bootstrappers, 2, 0, [
            'Statamic\Bootstrap\UpdateConfiguration'
        ]);

        return $this->bootstrappers;
    }
}
