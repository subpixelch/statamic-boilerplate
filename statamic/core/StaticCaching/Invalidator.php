<?php

namespace Statamic\StaticCaching;

use Statamic\API\Config;
use Statamic\Contracts\Data\Entries\Entry;
use Statamic\Contracts\Data\Content\Content;
use Statamic\Contracts\Data\Taxonomies\Term;

class Invalidator
{
    /**
     * @var array
     */
    protected $rules;

    /**
     * @var \Statamic\StaticCaching\Cacher
     */
    private $cacher;

    /**
     * @param \Statamic\StaticCaching\Cacher $cacher
     */
    public function __construct(Cacher $cacher)
    {
        $this->cacher = $cacher;
    }

    /**
     * Handle the event and invalidate the appropriate urls
     *
     * @param \Statamic\Contracts\Data\Content\Content $content
     */
    public function handle(Content $content)
    {
        // Get the invalidation rule scheme
        $this->rules = Config::get('caching.static_caching_invalidation');

        // Invalidate the content's own URL.
        $this->invalidateUrl($content->url());

        // Call the specialized method based on the content type, eg. invalidateEntryUrls
        $method = 'invalidate'.ucfirst($content->contentType()).'Urls';
        if (method_exists($this, $method)) {
            $this->$method($content);
        }
    }

    /**
     * Invalidate a specific URL
     *
     * @param string $url
     */
    protected function invalidateUrl($url)
    {
        $this->cacher->invalidateUrl($url);
    }

    /**
     * Invalidate URLs for an entry
     *
     * @param Entry $entry
     */
    protected function invalidateEntryUrls(Entry $entry)
    {
        $collection = $entry->collectionName();

        $urls = array_get($this->rules, "collections.$collection.urls", []);

        $this->cacher->invalidateUrls($urls);
    }

    /**
     * Invalidate URLs for a taxonomy term
     *
     * @param Term $term
     */
    protected function invalidateTermUrls(Term $term)
    {
        $taxonomy = $term->taxonomyName();

        $urls = array_get($this->rules, "taxonomies.$taxonomy.urls", []);

        $this->cacher->invalidateUrls($urls);
    }
}
