<?php

namespace Statamic\Data\Services;

use Statamic\API\Helper;
use Statamic\Contracts\Data\Taxonomies\Term;
use Statamic\Data\Taxonomies\TermCollection;
use Statamic\Stache\Repository;

class TermsService extends AbstractService
{
    /**
     * The repo key
     *
     * @var string
     */
    protected $repo = 'terms';

    /**
     * Get a term by ID
     *
     * @param string $id
     * @return Term
     */
    public function id($id)
    {
        return $this->repo()->getRepoById($id)->getItem($id);
    }

    /**
     * Check if a term exists by ID
     *
     * @param string $id
     * @return bool
     */
    public function exists($id)
    {
        return ! $this->repo()->repos()->map(function (Repository $repo) use ($id) {
            return $repo->getIds()->has($id);
        })->filter()->isEmpty();
    }

    /**
     * Get a term by slug
     *
     * @param string      $slug
     * @param string|null $taxonomy  Optionally restrict to a taxonomy
     * @return Term
     */
    public function slug($slug, $taxonomy = null)
    {
        $items = ($taxonomy)
            ? $this->taxonomy($taxonomy)
            : $this->repo()->getItems();

        return $items->first(function ($id, $term) use ($slug, $taxonomy) {
            return $term->slug() === $slug;
        });
    }

    /**
     * Check if a term exists with a given slug
     *
     * @param string $slug
     * @param string|null $taxonomy
     * @return bool
     */
    public function slugExists($slug, $taxonomy = null)
    {
        return $this->slug($slug, $taxonomy) !== null;
    }

    /**
     * Get a term by URI
     *
     * @param string $uri
     * @return Term
     */
    public function uri($uri)
    {
        $id = $this->repo()->repos()->map(function (Repository $repo) use ($uri) {
            return $repo->getIdByUri($uri);
        })->filter()->first();

        return $this->id($id);
    }

    /**
     * Get all terms
     *
     * @return TermCollection
     */
    public function all()
    {
        return collect_terms($this->repo()->repos()->flatMap(function ($repo) {
            return $repo->getItems();
        }));
    }

    /**
     * Get all the terms in a taxonomy
     *
     * @param string $taxonomy
     * @return TermCollection
     */
    public function taxonomy($taxonomy)
    {
        return $this->taxonomies(Helper::ensureArray($taxonomy));
    }

    /**
     * Get all the terms in multiple taxonomies
     *
     * @param array $taxonomies
     * @return TermCollection
     */
    public function taxonomies($taxonomies)
    {
        $terms = collect_terms();

        foreach ($taxonomies as $taxonomy) {
            $terms = $terms->merge(
                $this->repo()->repo($taxonomy)->getItems()
            );
        }

        return $terms;
    }
}