<?php

namespace Statamic\StaticCaching;

use Statamic\API\Str;
use Statamic\API\Config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Cache\Repository;

abstract class AbstractCacher implements Cacher
{
    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @param Repository $cache
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return int
     */
    protected function getDefaultExpiration()
    {
        return Config::get('caching.static_caching_default_cache_length');
    }

    /**
     * @param  mixed $content
     * @return string
     */
    protected function normalizeContent($content)
    {
        if ($content instanceof Response) {
            $content = $content->content();
        }

        return $content;
    }

    /**
     * Prefix a cache key
     *
     * @param string $key
     * @return string
     */
    protected function normalizeKey($key)
    {
        return "static_cache.$key";
    }

    /**
     * Place a key into the cache
     *
     * @param string   $key         Key to add
     * @param string   $value       Value to add
     * @param int|null $expiration  Expiration in minutes, or null for indefinite
     */
    protected function putInCache($key, $value, $expiration = null)
    {
        $key = $this->normalizeKey($key);

        if ($expiration) {
            $this->cache->put($key, $value, $expiration);
        } else {
            $this->cache->forever($key, $value);
        }
    }

    /**
     * Get a hashed string representation of a URL
     *
     * @param string $url
     * @return string
     */
    protected function makeHash($url)
    {
        return md5($url);
    }

    /**
     * Get the URL from a request
     *
     * @param Request $request
     * @return string
     */
    protected function getUrl(Request $request)
    {
        $url = $request->path();

        if (! Config::get('static_caching_ignore_query_strings')) {
            if ($query = http_build_query($request->query->all())) {
                $url .= '?' . $query;
            }
        }

        return Str::ensureLeft($url, '/');
    }

    /**
     * Get all the URLs that have been cached
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getUrls()
    {
        return collect($this->cache->get($this->normalizeKey('urls'), []));
    }

    /**
     * Flush all the cached URLs
     *
     * @return void
     */
    protected function flushUrls()
    {
        $this->cache->forget($this->normalizeKey('urls'));
    }

    /**
     * Save a URL to the cache
     *
     * @param string $key
     * @param string $url
     * @return void
     */
    protected function cacheUrl($key, $url)
    {
        $urls = $this->getUrls();

        $urls->put($this->normalizeKey($key), $url);

        $this->cache->forever($this->normalizeKey('urls'), $urls->all());
    }

    /**
     * Forget / remove a URL from the cache by its key
     *
     * @param string $key
     * @return void
     */
    protected function forgetUrl($key)
    {
        $urls = $this->getUrls();

        $urls->forget($key);

        $this->cache->forever($this->normalizeKey('urls'), $urls->all());
    }

    /**
     * Invalidate a wildcard URL
     *
     * @param string $wildcard
     */
    protected function invalidateWildcardUrl($wildcard)
    {
        // Remove the asterisk
        $wildcard = substr($wildcard, 0, -1);

        $this->getUrls()->filter(function ($url) use ($wildcard) {
            return Str::startsWith($url, $wildcard);
        })->each(function ($url) {
            $this->invalidateUrl($url);
        });
    }

    /**
     * Invalidate multiple URLs
     *
     * @param array $urls
     * @return void
     */
    public function invalidateUrls($urls)
    {
        collect($urls)->each(function ($url) {
            if (Str::contains($url, '*')) {
                $this->invalidateWildcardUrl($url);
            } else {
                $this->invalidateUrl($url);
            }
        });
    }

    /**
     * Determine if a given URL should be excluded from caching
     *
     * @param string $url
     * @return bool
     */
    protected function isExcluded($url)
    {
        $exclusions = collect(Config::get('caching.static_caching_exclude', []));

        foreach ($exclusions as $excluded) {
            if (Str::endsWith($excluded, '*') && Str::startsWith($url, substr($excluded, 0, -1))) {
                return true;
            }

            if ($url === $excluded) {
                return true;
            }
        }

        return false;
    }
}
