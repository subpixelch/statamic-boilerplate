<?php

namespace Statamic\Data\Taxonomies;

use Statamic\API\File;
use Statamic\API\Term as TermAPI;
use Statamic\API\YAML;
use Statamic\API\Config;
use Statamic\API\Folder;
use Statamic\API\Fieldset;
use Statamic\Data\DataFolder;
use Statamic\Contracts\Data\Taxonomies\Taxonomy as TaxonomyContract;

class Taxonomy extends DataFolder implements TaxonomyContract
{
    /**
     * @var \Statamic\Data\Taxonomies\TermCollection
     */
    protected $terms;

    /**
     * @var \Carbon\Carbon
     */
    protected $last_modified;

    /**
     * @var string|null
     */
    protected $route;

    /**
     * @var string|null
     */
    protected $original_route;

    /**
     * @return int
     */
    public function count()
    {
        return $this->terms()->count();
    }

    /**
     * @return \Statamic\Data\Taxonomies\TermCollection
     */
    public function terms()
    {
        if ($this->terms) {
            return $this->terms;
        }

        if (! $taxonomies = TermAPI::whereTaxonomy($this->path())) {
            $taxonomies = collect_terms();
        }

        return $this->terms = $taxonomies;
    }

    /**
     * @param string                                   $key
     * @param \Statamic\Contracts\Data\Taxonomies\Term $term
     */
    public function addTerm($key, $term)
    {
        $this->terms->put($key, $term);
    }

    /**
     * @param string $key
     */
    public function removeTerm($key)
    {
        $this->terms->pull($key);
    }

    /**
     * @return \Carbon\Carbon
     */
    public function lastModified()
    {
        $date = null;

        foreach ($this->terms() as $taxonomy) {
            $modified = $taxonomy->getLastModified();

            if ($date) {
                if ($modified->gt($date)) {
                    $date = $modified;
                }
            } else {
                $date = $modified;
            }
        }

        return $date;
    }

    /**
     * @return mixed
     */
    public function save()
    {
        $path = 'taxonomies/' . $this->path() . '/folder.yaml';

        File::disk('content')->put($path, YAML::dump($this->data()));

        // If the route was modified, update routes.yaml
        if ($this->route && ($this->original_route !== $this->route)) {
            Config::set('routes.taxonomies.'.$this->path(), $this->route());
            Config::save();
        }
    }

    /**
     * Delete the folder
     *
     * @return mixed
     */
    public function delete()
    {
        Folder::disk('content')->delete('taxonomies/' . $this->path());
    }

    /**
     * Get the URL to edit this in the CP
     *
     * @return string
     */
    public function editUrl()
    {
        return cp_route('taxonomy.edit', $this->path());
    }

    /**
     * Get or set the route definition
     *
     * @return string
     */
    public function route($route = null)
    {
        if (is_null($route)) {
            return $this->route ?: array_get(Config::getRoutes(), 'taxonomies.'.$this->path());
        }

        if (! $this->original_route) {
            $this->original_route = $this->route();
        }

        $this->route = $route;
    }

    /**
     * Get or set the fieldset
     *
     * @param string|null|bool
     * @return Statamic\Contracts\CP\Fieldset
     */
    public function fieldset($fieldset = null)
    {
        if (! is_null($fieldset)) {
            $this->set('fieldset', $fieldset);
        }

        if ($fieldset === false) {
            $this->set('fieldset', null);
        }

        return Fieldset::get([
            $this->get('fieldset'),
            Config::get('theming.default_term_fieldset'),
            Config::get('theming.default_fieldset')
        ]);
    }
}
