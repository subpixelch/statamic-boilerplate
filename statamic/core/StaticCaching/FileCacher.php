<?php

namespace Statamic\StaticCaching;

use Statamic\API\File;
use Statamic\API\Config;
use Statamic\API\Folder;
use Illuminate\Http\Request;
use League\Flysystem\FileNotFoundException;

class FileCacher extends AbstractCacher
{
    /**
     * @var string
     */
    private $cache_path;

    /**
     * Cache a page
     *
     * @param \Illuminate\Http\Request $request     Request associated with the page to be cached
     * @param string                   $content     The response content to be cached
     * @param null|int                 $expiration  Length of time to cache for, in minutes.
     *                                              Null to use the configured default.
     */
    public function cachePage(Request $request, $content, $expiration = null)
    {
        $url = $this->getUrl($request);

        if ($this->isExcluded($url)) {
            return;
        }

        $content = $this->normalizeContent($content);

        $path = $this->cachePath() . '/' . $request->path() . '/index.html';

        File::put($path, $content);

        $this->cacheUrl($this->makeHash($url), $url);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getCachedPage(Request $request)
    {
        // This method doesn't get used when using file-based static caching.
        // The html file will get served before PHP even gets a chance.
    }

    /**
     * Flush out the entire static cache
     *
     * @return void
     */
    public function flush()
    {
        foreach (Folder::getFilesRecursively($this->cachePath()) as $path) {
            File::delete($path);
        }

        Folder::deleteEmptySubfolders($this->cachePath());

        $this->flushUrls();
    }

    /**
     * Invalidate a URL
     *
     * @param string $url
     * @return void
     */
    public function invalidateUrl($url)
    {
        if (! $key = $this->getUrls()->flip()->get($url)) {
            // URL doesn't exist, nothing to invalidate.
            return;
        }

        try {
            File::delete($this->cachePath() . $url . 'index.html');
        } catch (FileNotFoundException $e) {
            //
        }

        $this->forgetUrl($key);
    }

    /**
     * Get the path where static files are stored
     *
     * @return string
     */
    private function cachePath()
    {
        if ($this->cache_path) {
            return $this->cache_path;
        }

        return $this->cache_path = Config::get('caching.static_caching_file_path');

    }
}
