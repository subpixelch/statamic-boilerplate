<?php

namespace Statamic\Data\Taxonomies;

use Statamic\API\Config;
use Statamic\API\Entry;
use Statamic\API\Fieldset;
use Statamic\API\File;
use Statamic\API\Path;
use Statamic\API\Taxonomy as TaxonomyAPI;
use Statamic\Contracts\Data\Taxonomies\Term as TermContract;
use Statamic\Data\Content\Content;
use Statamic\Data\Content\ContentCollection;
use Statamic\Data\Content\HasLocalizedSlugsInData;
use Statamic\Contracts\Data\Taxonomies\Taxonomy as TaxonomyContract;

class Term extends Content implements TermContract
{
    /**
     * Allows localized slugs to be placed in front matter
     *
     * Used by entries and terms
     */
    use HasLocalizedSlugsInData;

    /**
     * The content that is associated to this term.
     *
     * @var ContentCollection
     */
    private $collection;

    /**
     * Get or set the path
     *
     * @param string|null $path
     * @return string
     */
    public function path($path = null)
    {
        if (! is_null($path)) {
            dd('todo: set a terms path in term@path'); // @todo
        }

        if (isset($this->attributes['path'])) {
            return $this->attributes['path'];
        }

        return $this->buildPath();
    }

    /**
     * Get the path to a localized version
     *
     * @param string $locale
     * @return string
     */
    public function localizedPath($locale)
    {
        return $this->buildPath(compact('locale'));
    }

    /**
     * Get the path before the object was modified.
     *
     * @return string
     */
    public function originalPath()
    {
        $attr = $this->original['attributes'];

        return $this->buildPath($attr);
    }

    /**
     * Get the path to a localized version before the object was modified.
     *
     * @param string $locale
     * @return string
     */
    public function originalLocalizedPath($locale)
    {
        $attr = $this->original['attributes'];

        $attr['locale'] = $locale;

        return $this->buildPath($attr);
    }

    /**
     * Dynamically build the file path
     *
     * @param array $data Overrides for any arguments.
     * @return string
     */
    private function buildPath($data = [])
    {
        return app('Statamic\Contracts\Data\Content\PathBuilder')
            ->term()
            ->slug(array_get($data, 'slug', $this->attributes['slug']))
            ->taxonomy(array_get($data, 'taxonomy', $this->taxonomyName()))
            ->published(array_get($data, 'published', $this->published()))
            ->order(array_get($data, 'order', $this->order()))
            ->extension(array_get($data, 'data_type', $this->dataType()))
            ->locale(array_get($data, 'locale', $this->locale()))
            ->get();
    }

    /**
     * Get or set the associated taxonomy
     *
     * @param TaxonomyContract|string|null $taxonomy
     * @return TaxonomyContract
     */
    public function taxonomy($taxonomy = null)
    {
        if (is_null($taxonomy)) {
            return TaxonomyAPI::whereHandle($this->attributes['taxonomy']);
        }

        // If we've been passed an actual collection, we just need the name of it.
        if ($taxonomy instanceof TaxonomyContract) {
            $taxonomy = $taxonomy->basename();
        }

        $this->attributes['taxonomy'] = $taxonomy;
    }

    /**
     * Get or set the name of the associated taxonomy
     *
     * @param string|null $name
     * @return string
     */
    public function taxonomyName($name = null)
    {
        if (is_null($name)) {
            return $this->attributes['taxonomy'];
        }

        $this->attributes['taxonomy'] = $name;
    }

    /**
     * Get or set the content that is related to this term
     *
     * @param ContentCollection|null $collection
     * @return ContentCollection
     */
    public function collection(ContentCollection $collection = null)
    {
        if (! is_null($collection)) {
            return $this->collection = $collection;
        }

        // If a collection has been set explicitly, use that instead of fetching dynamically.
        if ($this->collection) {
            return $this->collection;
        }

        // If there's no ID, we're probably dealing with a temporary term, like from
        // within a Sneak Peek. In that case, don't bother. There's no content.
        if (! $this->id()) {
            return collect_content();
        }

        return $this->collection = collect_content(
            Entry::all()->filterByTaxonomy($this->id())
        );
    }

    /**
     * Get or set the URI
     *
     * This is the "identifying URL" for lack of a better description.
     * For instance, where `/fr/blog/my-post` would be a URL, `/blog/my-post` would be the URI.
     *
     * @param string|null $uri
     * @return mixed
     * @throws \Exception
     */
    public function uri($uri = null)
    {
        if ($uri) {
            throw new \Exception('Cannot set the URL on an entry directly.');
        }

        $routes = array_get(Config::getRoutes(), 'taxonomies', []);

        if (! $route = array_get($routes, $this->taxonomyName())) {
            return false;
        }

        return app('Statamic\Contracts\Data\Content\UrlBuilder')->content($this)->build($route);
    }

    /**
     * Get the URL localized to the current locale
     *
     * @return string
     */
    public function localizedUrl()
    {
        $routes = array_get(Config::getRoutes(), 'taxonomies', []);

        if (! $route = array_get($routes, $this->taxonomyName())) {
            return false;
        }

        return app('Statamic\Contracts\Data\Content\UrlBuilder')->content($this)->build($route);
    }

    /**
     * The URL to edit it in the CP
     *
     * @return mixed
     */
    public function editUrl()
    {
        return cp_route('term.edit', [$this->taxonomyName(), $this->slug()]);
    }

    /**
     * Get data from the cascade (folder.yaml files)
     *
     * @return array
     */
    protected function cascadingData()
    {
        return $this->taxonomy()->data();
    }

    /**
     * Get or set the template
     *
     * @param string|null $template
     * @return mixed
     */
    public function template($template = null)
    {
        return [
            $this->getWithCascade('template'), // gets `template` from the entry, and falls back to what's in folder.yaml
            $this->taxonomyName(),
            Config::get('theming.default_taxonomy_template'),
            Config::get('theming.default_page_template')
        ];
    }

    /**
     * Get or set the layout
     *
     * @param string|null $layout
     * @return mixed
     */
    public function layout($layout = null)
    {
        if (is_null($layout)) {
            // First, check the front-matter
            if ($layout = $this->getWithCascade('layout')) {
                return $layout;
            }

            // Lastly, return a default
            return Config::get('theming.default_layout');
        }

        $this->set('layout', $layout);
    }

    /**
     * Get the folder of the file relative to content path
     *
     * @return string
     */
    public function folder()
    {
        $dir = Path::directory($this->path());

        $dir = preg_replace('#^taxonomies/#', '', $dir);

        return (str_contains($dir, '/')) ? explode('/', $dir)[0] : $dir;
    }

    /**
     * Get the fieldset
     *
     * @return string|null
     */
    protected function getFieldset()
    {
        // First check the front matter
        if ($fieldset = $this->getWithCascade('fieldset')) {
            return Fieldset::get($fieldset);
        }

        // Then the default content fieldset
        $fieldset = Config::get('theming.default_' . $this->contentType() . '_fieldset');
        $path = settings_path('fieldsets/'.$fieldset.'.yaml');
        if (File::exists($path)) {
            return Fieldset::get($fieldset);
        }

        // Finally the default fieldset
        return Fieldset::get(Config::get('theming.default_fieldset'));
    }

    /**
     * Get the number of content objects that related to this taxonomy
     *
     * @return int
     */
    public function count()
    {
        return $this->collection()->count();
    }

    /**
     * Add supplemental data to the attributes
     *
     * Some data on the taxonomy is dynamic and only available through methods.
     * When we want to use these when preparing for use in a template for
     * example, we will need these available in the front-matter.
     */
    public function supplement()
    {
        parent::supplement();

        $this->supplements['taxonomy_group'] = $this->taxonomyName(); // @todo: remove
        $this->supplements['taxonomy'] = $this->taxonomyName();
        $this->supplements['count'] = $this->count();
        $this->supplements['is_term'] = true;
        $this->supplements['results'] = $this->count();

        $this->supplements = array_merge($this->cascadingData(), $this->supplements);
    }
}