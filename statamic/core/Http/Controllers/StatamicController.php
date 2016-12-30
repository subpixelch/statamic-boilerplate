<?php

namespace Statamic\Http\Controllers;

use Exception;
use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\Path;
use Statamic\API\User;
use Statamic\API\Event;
use Statamic\Http\View;
use Statamic\API\Config;
use Statamic\API\Folder;
use Statamic\API\Content;
use Statamic\API\Pattern;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\CP\Publish\SneakPeek;
use DebugBar\DataCollector\ConfigCollector;

/**
 * The front-end controller
 */
class StatamicController extends Controller
{
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var \Statamic\Contracts\Data\Content\Content
     */
    private $data;

    /**
     * @var mixed
     */
    private $response;

    /**
     * @var bool
     */
    private $peeking;

    /**
     * @var SneakPeek
     */
    private $sneak_peek;

    /**
     * Create a new StatamicController
     *
     * @param \Illuminate\Http\Request $request
     * @param \Statamic\Http\View      $view
     */
    public function __construct(Request $request, View $view)
    {
        $this->request = $request;
        $this->view = $view;
    }

    /**
     * Trigger either a controller method if it exists, or emit an event
     *
     * @param string|null $name
     * @param string|null $method
     * @param string|null $parameters
     * @return \Illuminate\Http\Response
     */
    public function controllerTrigger($name = null, $method = null, $parameters = null)
    {
        // If an incomplete URL was sent, we'll treat it as a 404.
        if (! $name || ! $method) {
            return abort(404);
        }

        // The params will come through the URL as segments.
        // We'll convert that to an array of strings.
        if ($parameters) {
            $parameters = explode('/', $parameters);
        }

        if ($response = $this->callControllerMethod($name, $method, $parameters)) {
            return $response;
        }

        return $this->fireEvent($name, $method, $parameters);
    }

    /**
     * Call an addon's controller method and inject any dependencies
     *
     * @param string $name
     * @param string $method
     * @param array $parameters
     * @return bool|\Illuminate\Http\Response
     */
    private function callControllerMethod($name, $method, $parameters)
    {
        $studly = Str::studly($name);
        $controller = "Statamic\\Addons\\$studly\\{$studly}Controller";

        if (! class_exists($controller)) {
            return false;
        }

        $method = strtolower($this->request->method()) . Str::studly($method);

        return app()->call($controller.'@'.$method, $parameters ?: []);
    }

    /**
     * Fire an event
     *
     * @param string  $namespace   URL segment 1
     * @param string  $event       URL segment 2
     * @param array   $parameters  Additional data
     * @return \Illuminate\Http\Response
     */
    private function fireEvent($namespace, $event, $parameters = [])
    {
        $response = array_get(Event::fire("{$namespace}.{$event}", $parameters), 0);

        // If a view has been returned from an event, we want to render it.
        if ($response instanceof \Illuminate\Contracts\View\View ||
            $response instanceof \Illuminate\Http\RedirectResponse ||
            $response instanceof \Illuminate\Http\Response
        ) {
            return $response;
        }

        return response('OK', 204);
    }

    /**
     * Handles all URLs
     *
     * URL: /{segments}
     *
     * @param string $segments
     * @return string
     */
    public function index($segments = '/')
    {
        $segments = $this->parseUrl($segments);

        // Are we sneaking a peek?
        if ($this->peeking = $this->request->has('preview')) {
            // Are we allowed to be sneaking a peek?
            if (! User::loggedIn() || ! User::getCurrent()->hasPermission('cp:access')) {
                return $this->notFoundResponse();
            }

            $this->sneak_peek = new SneakPeek($this->request);
        }

        // Prevent continuing if we're looking for a missing favicon
        if ($segments === 'favicon.ico') {
            return $this->notFoundResponse();
        }

        $url = URL::tidy('/' . preg_replace('#^'.SITE_ROOT.'#', '', '/'.$segments));

        // Perform a redirect if this is a vanity URL
        if ($vanity = $this->vanityRedirect($url)) {
            return $vanity;
        }

        // Perform a redirect if this URL has moved permanent
        if ($permanent = $this->permanentRedirect($url)) {
            return $permanent;
        }

        // Attempt to find the data for this URL. It might be content,
        // a route, or nada. If there's nothing, we'll send a 404.
        $this->data = $this->getDataForUri($url);
        if ($this->data === false) {
            return $this->notFoundResponse();
        }

        // Check for a redirect within the data
        if ($redirect = $this->getRedirectFromData()) {
            return $redirect;
        }

        // Check for any page protection
        $this->protect();

        // Unpublished content can only be viewed on the front-end if the user has appropriate permission
        if (is_object($this->data) && ! $this->data->published()) {
            $user = User::getCurrent();

            if (! $user || ! $user->hasPermission('content:view_drafts_on_frontend')) {
                return $this->notFoundResponse();
            }
        }

        // If we're sneaking a peek, we'll need to update the data for the content object.
        // At this point, we'll have either an existing content object, or a
        // new temporary one created by the SneakPeek class.
        if ($this->peeking) {
            $data = $this->sneak_peek->update($this->data);
            $this->data->data($data);
        }

        // Load some essential variables that will be available in the template.
        $this->loadKeyVars();

        $this->ensureTheme();

        // Get the output of the parsed template.
        $this->response = response($this->view->render($this->data));

        $this->setUpDebugBar();

        $this->modifyResponse();

        return $this->response;
    }

    /**
     * Parse the URL segments
     *
     * @param string $segments
     * @return array
     */
    private function parseUrl($segments)
    {
        // Remove ignored segments
        $segments = explode('/', $segments);
        $ignore = array_get(Config::getRoutes(), 'ignore', []);
        $remove_segments = array_intersect_key($ignore, $segments);
        $segments = join('/', array_diff($segments, $remove_segments));

        return $segments;
    }

    /**
     * Get the data from this URI
     *
     * @param string $uri
     * @return array|bool
     */
    private function getDataForUri($uri)
    {
        $requested_uri = $uri;

        // First we'll attempt to find a matching route.
        if ($route = $this->getRoute($uri)) {
            return $route;
        }

        // Get the default locale's URL for the given current URL.
        if ($default_uri = URL::getDefaultUri(site_locale(), $uri)) {
            $uri = $default_uri;
        }

        // Attempt to get the content at this URI
        if ($content = Content::whereUri($uri)) {
            // Place the content in the locale we want.
            $content = $content->in(site_locale());

            // If the requested URI exists, but also has a localized version, the
            // default URI should not be accessible. For example, if /team has
            // been localized to /equipe, visiting /about should throw a 404.
            if ($requested_uri === $content->uri()) {
                return $content;
            }
        }


        // Are we previewing a new page?
        if ($this->peeking) {
            return $this->sneak_peek->content();
        }

        // Still nothing?
        return false;
    }

    /**
     * Attempt to get the data for a route that matches a $url
     *
     * @param string $url
     * @return array|null
     */
    private function getRoute($url)
    {
        $standard_routes = [];

        $url = URL::prependSiteUrl($url);

        // The routes array is organized with the route as the key and either a string specifying a template,
        // or an array containing data. If just a string was provided, we'll transform it into an array so
        // everything is consistent. We aren't concerned with the collections and taxonomies arrays since
        // they would have been picked up earlier on when we were checking for content.
        foreach (array_get(Config::getRoutes(), 'routes', []) as $route_url => $route) {
            if (! is_array($route)) {
                $route = ['template' => $route];
            }

            $standard_routes[URL::prependSiteUrl($route_url)] = $route;
        }

        // At this point we have all the routes organized nicely into a route/data array.
        // We'll iterate over them and if there's a match, we'll return the route data.
        foreach ($standard_routes as $route => $data) {
            // Convert standard wildcards
            if (strpos($route, '*')) {
                $i = 0;
                $route = preg_replace_callback('/\*/', function ($matches) use (&$i) {
                    $i++;
                    return "{wildcard_$i}";
                }, $route);
            }

            // Check for named wildcards
            if (strpos($route, '{')) {
                // Get the named keys out of the route
                preg_match_all('/{\s*([a-zA-Z0-9_\-]+)\s*}/', $route, $matches);
                $named_keys = $matches[1];

                // Create a regex that we can use to get the wildcard values
                $regex = preg_replace('/{\s*[a-zA-Z0-9_\-]+\s*}/', '([^/]*)', str_replace('*', '\.', $route));

                // Check for a URL match
                if (preg_match('#^' . $regex . '$#i', $url, $matches)) {
                    array_shift($matches); // The first match is the whole URL. Remove it.

                    $wildcard_data = array_combine($named_keys, $matches);

                    return array_merge($data, $wildcard_data);
                }
            }

            // Regular check
            if ($url == $route) {
                return $data;
            }
        }

        // No matching route
        return null;
    }

    /**
     * Get a redirect response from data, if one has been specified using a `redirect` variable.
     *
     * @return null|RedirectResponse
     */
    private function getRedirectFromData()
    {
        $data = (is_object($this->data)) ? $this->data->toArray() : $this->data;

        if ($redirect = array_get($data, 'redirect')) {
            if ($redirect == '404') {
                abort(404);
            }

            return redirect($redirect);
        }
    }

    /**
     * Perform a vanity redirect
     *
     * @param  string $url URL to test
     * @return null|RedirectResponse  A redirect if a vanity route exists, or nothing.
     */
    private function vanityRedirect($url)
    {
        $routes = array_get(Config::getRoutes(), 'vanity', []);

        if (array_key_exists($url, $routes)) {
            return redirect($routes[$url]);
        }
    }

    /**
     * Perform a vanity redirect
     *
     * @param  string $url URL to test
     * @return null|RedirectResponse  A redirect if a vanity route exists, or nothing.
     */
    private function permanentRedirect($url)
    {
        $routes = array_get(Config::getRoutes(), 'redirect', []);

        if (array_key_exists($url, $routes)) {
            return redirect($routes[$url], 301);
        }
    }

    private function protect()
    {
        // First try to get a protection scheme from the system
        // settings then fall back to a scheme inside the data.
        if (! $scheme = Config::get('system.protect')) {
            $scheme = (is_object($this->data))
                ? $this->data->get('protect')
                : array_get($this->data, 'protect');
        }

        // If there's no protection scheme then we can move along.
        if (! $scheme) {
            return;
        }

        addon('Protect')->protect(URL::getCurrent(), $scheme);
    }

    private function ensureTheme()
    {
        $theme = Config::get('theming.theme');

        if (! Folder::disk('themes')->exists($theme)) {
            \Log::error("The [$theme] theme doesn't exist.");
        }
    }

    public function setUpDebugBar()
    {
        if (! Config::get('debug.debug_bar')) {
            return;
        }

        $data = datastore()->getAll();

        ksort($data);

        debugbar()->addCollector(new ConfigCollector($data, 'Variables'));
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    private function notFoundResponse()
    {
        $this->loadKeyVars();

        datastore()->merge([
            'response_code' => 404
        ]);

        $this->setUpDebugBar();

        $template = Str::removeLeft(Path::assemble(Config::get('theming.error_template_folder'), '404'), '/');

        return response($this->view->render([], $template), 404);
    }

    /**
     * Adjust the content type header of the request, if we want something other than HTML.
     */
    private function adjustResponseContentType($data)
    {
        $content_type = array_get($data, 'content_type', 'html');

        // If it's html, we don't need to continue.
        if ($content_type === 'html') {
            return;
        }

        // Translate simple content types to actual ones
        switch ($content_type) {
            case 'xml':
                $content_type = 'text/xml';
                break;
            case 'atom':
                $content_type = 'application/atom+xml; charset=UTF-8';
                break;
            case 'json':
                $content_type = 'application/json';
                break;
            case 'text':
                $content_type = 'text/plain';
        }

        // Adjust the response
        $this->response->header('Content-Type', $content_type);
    }

    /**
     * Modify the Response
     *
     * @return void
     */
    private function modifyResponse()
    {

        $data = (is_object($this->data)) ? $this->data->toArray() : $this->data;

        // Modify the response if we're attempting to serve something other than just HTML.
        $this->adjustResponseContentType($data);

        // Add a powered-by header, but only if it's cool with them.
        if (Config::get('system.send_powered_by_header')) {
            $this->response->header('X-Powered-By', 'Statamic');
        }

        // Allow users to set custom headers
        $headers = array_get($data, 'headers', []);

        foreach ($headers as $header => $value) {
            $this->response->header($header, $value);
        }

        // Allow addons to modify the response. They can add headers, modify the content, etc.
        // The event will get the Response object as a payload, which they simply need to modify.
        event('response.created', $this->response);
    }
}
