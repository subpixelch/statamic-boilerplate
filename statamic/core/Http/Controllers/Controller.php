<?php

namespace Statamic\Http\Controllers;

use Carbon\Carbon;
use Statamic\API\User;
use Statamic\API\Config;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Statamic\Exceptions\UnauthorizedHttpException;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use DispatchesJobs, ValidatesRequests, AuthorizesRequests;

    private $request;

    protected function access($area)
    {
        if (! User::getCurrent()->can($area)) {
            throw $this->createGateUnauthorizedException($area, []);
        }
    }

    protected function loadKeyVars()
    {
        $now = Carbon::now();

        $this->request = request();

        datastore()->merge(array_merge(
            [
                'site_url'     => Config::getSiteUrl(),
                'homepage'     => Config::getSiteUrl(),
                'current_url'  => $this->request->url(),
                'current_uri'  => format_url($this->request->path()),
                'current_date' => $now,
                'now'          => $now,
                'today'        => $now,
                'locale'       => site_locale(),
                'get'          => $this->request->query->all(),
                'post'         => ($this->request->isMethod('post')) ? $this->request->request->all() : [],
                'get_post'     => $this->request->all(),
                'response_code' => 200,
                'logged_in'    => \Auth::check(),
                'logged_out'   => !\Auth::check(),
                'environment'  => app()->environment(),
                'xml_header'   => '<?xml version="1.0" encoding="utf-8" ?>',
                'csrf_token'   => csrf_token(),
                'csrf_field'   => csrf_field(),
                'settings'     => Config::all()
            ],
            $this->segments()
        ));
    }

    private function segments()
    {
        $data = [];

        $segments = $this->request->segments();

        foreach ($segments as $key => $value) {
            $data['segment_' . ($key + 1)] = $value;
        }

        $data['last_segment'] = last($segments);

        return $data;
    }

    /**
     * Throw an unauthorized exception based on gate results.
     *
     * @param  string  $ability
     * @param  mixed|array  $arguments
     * @param  string  $message
     * @param  \Exception  $previousException
     * @return \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function createGateUnauthorizedException($ability, $arguments, $message = 'This action is unauthorized.', $previousException = null)
    {
        return new UnauthorizedHttpException(403, $message, $previousException);
    }
}
