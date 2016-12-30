<?php

namespace Statamic\Http\ResourceServer;

use Carbon\Carbon;
use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\Http\ResourceServer;
use Illuminate\Filesystem\Filesystem;

class Server
{
    const RESOURCE_ADDON = 'addon';

    const RESOURCE_CP = 'cp';

    /**
     * @var Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * The type of resource. CP or Addon.
     *
     * @var string
     */
    private $resource_type;

    /**
     * Full filesystem path to the location of the resouces
     *
     * @var string
     */
    private $base_path;

    /**
     * URI to the resource, without the actual resource
     *
     * @var string
     */
    private $base_uri;

    /**
     * The URI of the resource, without the base URI
     *
     * @var string
     */
    private $resource_uri;

    /**
     * Full path to the resource file
     *
     * @var string
     */
    private $path;

    /**
     * The name of the addon when using an an addon resource
     *
     * @var string
     */
    private $addon;

    /**
     * Last modified time of the file
     *
     * @var int
     */
    private $last_modified;

    /**
     * Mime types
     *
     * @var array
     */
    private $mime_types;

    /**
     * Create a new server
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem;

        $this->base_uri = URL::assemble(SITE_ROOT, pathinfo($_SERVER['SCRIPT_NAME'])['basename'], RESOURCES_ROUTE);

        $this->setResourceType();

        $this->setResourceUri();

        $this->setBasePath();

        $this->path = $this->base_path . '/' . $this->resource_uri;
    }

    /**
     * Set the type of resource. CP or Addon.
     *
     * If it's neither, throw a 404.
     *
     * @return void
     */
    private function setResourceType()
    {
        // Ensure index.php is in the URI.
        $uri = Str::ensureLeft($_SERVER['REQUEST_URI'], SITE_ROOT.pathinfo($_SERVER['SCRIPT_NAME'])['basename']);

        $uri = Str::removeLeft($uri, $this->base_uri);

        if (Str::startsWith($uri, '/cp')) {
            $this->resource_type = self::RESOURCE_CP;
        } elseif (Str::startsWith($uri, '/addon')) {
            $this->resource_type = self::RESOURCE_ADDON;
            $this->addon = explode('/', $uri)[2];
        } else {
            $this->serve404Response();
        }
    }

    /**
     * Set the resource uri, which is the path relative from the base path
     *
     * @return void
     */
    private function setResourceUri()
    {
        // Get the URI, which would be everything after index.php. This might be
        // after /index.php for a regular install, or after /subdir/index.php,
        // or even /sub/subdir/subsubdir/index.php for a bonkers install.
        $uri = Str::removeLeft($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);

        $uri = explode('?', $uri)[0];

        $parts = explode('/', $uri);

        if ($this->resource_type === self::RESOURCE_CP) {
            $parts = array_slice($parts, 3);
        } elseif ($this->resource_type === self::RESOURCE_ADDON) {
            $parts = array_slice($parts, 4);
        }

        $this->resource_uri = join('/', $parts);
    }

    /**
     * Set the base path depending on whether its a CP or Addon request
     *
     * @return void
     */
    private function setBasePath()
    {
        if ($this->resource_type === self::RESOURCE_CP) {
            $this->base_path = realpath(__DIR__ . '/../../../resources/dist');
        } elseif ($this->resource_type === self::RESOURCE_ADDON) {
            $this->base_path = realpath(__DIR__.'/../../../../site/addons/'.$this->addon.'/resources/assets');
        }
    }

    /**
     * Serve a resource
     *
     * @return mixed
     */
    public function serve()
    {
        date_default_timezone_set('America/New_York');

        $this->serve404Response();

        $this->serveNotModifiedResponse();

        $this->setHeaders();

        die($this->filesystem->get($this->path));
    }

    /**
     * Serve a 404 response if the file doesnt exist
     */
    private function serve404Response()
    {
        if (! $this->filesystem->exists($this->path)) {
            Headers::set('HTTP/1.0 404 Not Found');
            die('Resource not found');
        }
    }

    /**
     * Serve a not modified response, if possible.
     */
    private function serveNotModifiedResponse()
    {
        // Do nothing if the request header hasn't been sent.
        if (! array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
            return;
        }

        $date = Carbon::parse($_SERVER['HTTP_IF_MODIFIED_SINCE'])->timestamp;

        if ($this->getLastModified() <= $date) {
            Headers::set('HTTP/1.1 304 Not Modified');
            exit();
        }
    }

    /**
     * Get a MIME type based on the file extension
     *
     * @return string
     */
    private function getMimeType()
    {
        if (! $this->mime_types) {
            $this->mime_types = require 'mimes.php';
        }

        return array_get($this->mime_types, $this->filesystem->extension($this->path), 'text/plain');
    }

    /**
     * Get the last modified time of the resource
     *
     * @return int
     */
    private function getLastModified()
    {
        if (! $this->last_modified) {
            $this->last_modified = $this->filesystem->lastModified($this->path);
        }

        return $this->last_modified;
    }

    /**
     * Set appropriate headers
     */
    private function setHeaders()
    {
        Headers::set('Content-Type: '.$this->getMimeType().'; charset=utf-8');

        $cache_time = 31536000; // 1 year

        Headers::set([
            'Expires' => gmdate('D, d M Y H:i:s', time() + $cache_time).' GMT',
            'Pragma' => 'cache',
            'Cache-Control' => 'max-age='.$cache_time,
            'Last-Modified' => gmdate("D, d M Y H:i:s", $this->getLastModified()).' GMT'
        ]);
    }
}
