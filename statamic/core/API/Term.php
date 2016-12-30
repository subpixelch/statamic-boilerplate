<?php

namespace Statamic\API;

use Statamic\Data\Services\TermsService;

class Term
{
    /**
     * The service for interacting with term
     *
     * @return TermsService
     */
    private static function service()
    {
        return app(TermsService::class);
    }

    /**
     * Find an term by ID
     *
     * @param string $id
     * @return \Statamic\Contracts\Data\Taxonomies\Term
     */
    public static function find($id)
    {
        return self::service()->id($id);
    }

    /**
     * Get all terms
     *
     * @return \Statamic\Data\Taxonomies\TermCollection
     */
    public static function all()
    {
        return self::service()->all();
    }

    /**
     * Get terms in a taxonomy
     *
     * @param string $taxonomy
     * @return \Statamic\Data\Taxonomies\TermCollection
     */
    public static function whereTaxonomy($taxonomy)
    {
        return self::service()->taxonomy($taxonomy);
    }

    /**
     * Get a term by slug and taxonomy
     *
     * @param string $slug
     * @param string $taxonomy
     * @return \Statamic\Contracts\Data\Taxonomies\Term
     */
    public static function whereSlug($slug, $taxonomy)
    {
        return self::service()->slug($slug, $taxonomy);
    }

    /**
     * Get a term by URI
     *
     * @param string $uri
     * @return \Statamic\Contracts\Data\Taxonomies\Term
     */
    public static function whereUri($uri)
    {
        return self::service()->uri($uri);
    }

    /**
     * Check if a term exists
     *
     * @param string $id
     * @return bool
     */
    public static function exists($id)
    {
        return self::service()->exists($id);
    }

    /**
     * Check if a term exists by slug
     *
     * @param string $slug
     * @param string $taxonomy
     * @return bool
     */
    public static function slugExists($slug, $taxonomy)
    {
        return self::service()->slugExists($slug, $taxonomy);
    }

    /**
     * Create a term
     *
     * @param string $slug
     * @return \Statamic\Contracts\Data\Taxonomies\TermFactory
     */
    public static function create($slug)
    {
        return app('Statamic\Contracts\Data\Taxonomies\TermFactory')->create($slug);
    }
}
