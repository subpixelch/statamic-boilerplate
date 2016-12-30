<?php

namespace Statamic\StaticCaching;

use Illuminate\Http\Request;
use Illuminate\Contracts\Cache\Repository;

interface Cacher
{
    /**
     * @param Repository $cache
     */
    public function __construct(Repository $cache);

    /**
     * Cache a page
     *
     * @param \Illuminate\Http\Request $request     Request associated with the page to be cached
     * @param string                   $content     The response content to be cached
     * @param null|int                 $expiration  Length of time to cache for, in minutes.
     *                                              Null to use the configured default.
     */
    public function cachePage(Request $request, $content, $expiration = null);

    /**
     * Get a cached page
     *
     * @param Request $request
     * @return string
     */
    public function getCachedPage(Request $request);

    /**
     * Flush out the entire static cache
     *
     * @return void
     */
    public function flush();

    /**
     * Invalidate a URL
     *
     * @param string $url
     * @return void
     */
    public function invalidateUrl($url);

    /**
     * Invalidate multiple URLs
     *
     * @param array $urls
     * @return void
     */
    public function invalidateUrls($urls);
}
