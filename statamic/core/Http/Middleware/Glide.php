<?php

namespace Statamic\Http\Middleware;

use Cache;
use Closure;
use Statamic\API\Str;
use Statamic\API\Config;
use League\Glide\Server;

class Glide
{
    /**
     * @var \League\Glide\Server
     */
    private $server;

    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @param \League\Glide\Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
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

        if (! $this->isGlideRoute()) {
            return $next($request);
        }

        if ($path = $this->getGlidePathFromCache()) {
            return $this->server->getResponseFactory()->create($this->server->getCache(), $path);
        }

        return $next($request);
    }

    /**
     * Determine if the current route is the Glide route
     *
     * @return bool
     */
    private function isGlideRoute()
    {
        $glide_route = ltrim(Str::ensureRight(Config::get('assets.image_manipulation_route'), '/'), '/');

        return Str::startsWith($this->request->path(), $glide_route);
    }

    /**
     * Get the cached Glide path if one exists
     *
     * @return string|null
     */
    private function getGlidePathFromCache()
    {
        $key = md5($this->request->getUri());

        return Cache::get("glide::paths.$key");
    }
}
