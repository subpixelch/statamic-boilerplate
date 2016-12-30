<?php

namespace Statamic\Http\Middleware;

use Closure;
use Statamic\API\Str;
use Statamic\Outpost as StatamicOutpost;
use Statamic\Exceptions\UnlicensedException;

class Outpost
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var StatamicOutpost
     */
    private $outpost;

    /**
     * Create a new Middleware
     *
     * @param StatamicOutpost $outpost
     */
    public function __construct(StatamicOutpost $outpost)
    {
        $this->outpost = $outpost;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->request = $request;

        $this->outpost->radio();

        $this->protect();

        $this->setLicensing();

        $this->setUpdateAvailability();

        return $next($request);
    }

    /**
     * Prevent the CP from being usable if it's incorrectly licensed.
     * Obviously it's very simple to circumvent, but hey. Gotta try.
     *
     * If you're reading this in the hopes of bypassing licensing, you should
     * know that continuing to pirate our product will render our children
     * hungry and homeless. You don't really want that, do you? :(
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function protect()
    {
        // If it's not a request for the CP, let people through.
        if (! Str::startsWith($this->request->path(), CP_ROUTE)) {
            return;
        }

        // Allow users to enter their license key from the 503 page.
        if ($this->request->path() === CP_ROUTE.'/license-key') {
            return;
        }

        // It's not public, you're ok.
        if (! $this->outpost->isOnPublicDomain()) {
            return;
        }

        // Valid license and correct domain? You're awesome.
        if ($this->outpost->isLicenseValid() && $this->outpost->isOnCorrectDomain()) {
            return;
        }

        // Valid license but it's the wrong domain? We'll let you continue, but
        // provide a message in the CP prompting you to update the domain.
        if ($this->outpost->isLicenseValid() && !$this->outpost->isOnCorrectDomain()) {
            return;
        }

        // If you've made it this far. Relax, the police are on their way.
        throw new UnlicensedException;
    }

    /**
     * Set a notice if there is a newer version available
     *
     * @return void
     */
    private function setUpdateAvailability()
    {
        view()->share('update_available', $this->outpost->isUpdateAvailable());
    }

    /**
     * Set some view data related to licensing messages
     *
     * @return void
     */
    private function setLicensing()
    {
        view()->composer(['partials.nav-main', 'partials.alerts'], function ($view) {
            $view->with('is_trial', !$this->outpost->isLicenseValid());
        });

        view()->composer('partials.alerts', function ($view) {
            $correct = false;

            if (! $this->outpost->isOnPublicDomain()) {
                $correct = true;
            } elseif ($this->outpost->isOnPublicDomain() && $this->outpost->isOnCorrectDomain()) {
                $correct = true;
            }

            $view->with('is_correct_domain', $correct);

            $unlicensed = false;
            if (!$this->outpost->isLicenseValid() && $this->outpost->isOnPublicDomain()) {
                $unlicensed = true;
            }

            $view->with('is_unlicensed', $unlicensed);
        });
    }
}
