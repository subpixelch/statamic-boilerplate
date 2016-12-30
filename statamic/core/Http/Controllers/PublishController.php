<?php

namespace Statamic\Http\Controllers;

use Carbon\Carbon;
use Statamic\API\Collection;
use Statamic\API\Fieldset;
use Statamic\API\GlobalSet;
use Statamic\API\Taxonomy;
use Statamic\API\Term;
use Statamic\API\URL;
use Statamic\API\Page;
use Statamic\API\Entry;
use Statamic\API\Config;
use Statamic\API\Str;
use Statamic\API\Content;
use Stringy\StaticStringy as Stringy;
use Statamic\Exceptions\PublishException;

/**
 * Controller for the publish page
 */
class PublishController extends CpController
{
    /**
     * This "page"
     * @var \Statamic\Data\Page|\Statamic\Data\Entry
     */
    private $content;

    /**
     * Create a new page
     *
     * @param string $parent_url
     * @return \Illuminate\View\View
     */
    public function createPage($parent_url = '/')
    {
        $this->authorize('pages:create');

        $parent_url = Str::ensureLeft($parent_url, '/');

        if (! $parent = Page::whereUri($parent_url)) {
            return redirect(route('pages'))->withErrors("Page [$parent_url] doesn't exist.");
        }

        $fieldset = $this->request->query('fieldset', $parent->fieldset()->name());

        $data = $this->populateWithBlanks($fieldset);

        $extra = [
            'parent_url' => $parent_url
        ];

        return view('publish', [
            'extra'             => $extra,
            'is_new'            => true,
            'content_data'      => $data,
            'content_type'      => 'page',
            'fieldset'          => $fieldset,
            'title'             => translate('cp.create_page'),
            'uuid'              => null,
            'uri'               => null,
            'url'               => null,
            'slug'              => null,
            'status'            => true,
            'locale'            => default_locale(),
            'is_default_locale' => true,
            'locales'           => $this->getLocales()
        ]);
    }

    /**
     * Edit an existing page
     *
     * @param string $uri  URI of the page to edit. No URI indicates the home page.
     * @return \Illuminate\View\View
     */
    public function editPage($uri = '/')
    {
        $this->authorize('pages:edit');

        $uri = URL::format($uri);

        $locale = $this->request->query('locale', default_locale());

        if (! $page = Page::whereUri($uri)) {
            return redirect()->route('pages')->withErrors('No page found.');
        }

        $id = $page->id();

        $page = $page->in($locale)->get();

        $slug = $page->slug();
        $status = $page->published();

        $extra = [
            'is_home' => $page->uri() === '/',
            'parent_url' => URL::parent($uri)
        ];

        $data = $this->populateWithBlanks($page);

        return view('publish', [
            'extra'             => $extra,
            'is_new'            => false,
            'content_data'      => $data,
            'content_type'      => 'page',
            'fieldset'          => $page->fieldset()->name(),
            'title'             => array_get($data, 'title', $uri),
            'uuid'              => $id,
            'uri'               => $page->uri(),
            'url'               => $page->url(),
            'slug'              => $slug,
            'status'            => $status,
            'locale'            => $locale,
            'is_default_locale' => $page->isDefaultLocale(),
            'locales'           => $this->getLocales($id)
        ]);
    }

    /**
     * Create a new entry
     *
     * @param string $collection  The collection the entry will belong to
     * @return \Illuminate\View\View
     */
    public function createEntry($collection)
    {
        $this->authorize("collections:$collection:create");

        if (! $collection = Collection::whereHandle($collection)) {
            return redirect(route('collections'))->withErrors("Collection [$collection->path()] doesn't exist.");
        }

        $fieldset = $collection->fieldset();

        $data = $this->populateWithBlanks($fieldset->name());

        $extra = [
            'collection' => $collection->path(),
            'order_type' => $collection->order(),
            'route'      => $collection->route()
        ];

        if ($collection->order() === 'date') {
            $extra['datetime'] = Carbon::now()->format('Y-m-d');
        }

        $title = array_get(
                $fieldset->contents(),
                'create_title',
                t('create_entry', ['noun' => $fieldset->title()])
        );

        return view('publish', [
            'extra'             => $extra,
            'is_new'            => true,
            'content_data'      => $data,
            'content_type'      => 'entry',
            'fieldset'          => $fieldset->name(),
            'title'             => $title,
            'uuid'              => null,
            'uri'               => null,
            'url'               => null,
            'slug'              => null,
            'status'            => true,
            'locale'            => default_locale(),
            'is_default_locale' => true,
            'locales'           => $this->getLocales()
        ]);
    }

    /**
     * Edit an existing entry
     *
     * @param string $collection
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function editEntry($collection, $slug)
    {
        $this->authorize("collections:$collection:edit");

        $locale = $this->request->query('locale', site_locale());

        if (! $entry = Entry::whereSlug($slug, $collection)) {
            return redirect()->route('entries.show', $collection)->withErrors('No entry found.');
        }

        $id = $entry->id();

        $entry = $entry->in($locale)->get();

        $status = $entry->published();

        $extra = [
            'collection' => $collection,
            'default_slug' => $entry->slug(),
            'default_order' => $entry->order(),
            'order_type' => $entry->orderType()
        ];

        if ($entry->orderType() === 'date') {
            // Get the datetime without milliseconds
            $datetime = substr($entry->date()->toDateTimeString(), 0, 16);
            // Then strip off the time, if it's not supposed to be there.
            $datetime = ($entry->hasTime()) ? $datetime : substr($datetime, 0, 10);

            $extra['datetime'] = $datetime;
        }

        $data = $this->populateWithBlanks($entry);

        return view('publish', [
            'extra'              => $extra,
            'is_new'             => false,
            'content_data'       => $data,
            'content_type'       => 'entry',
            'fieldset'           => $entry->fieldset()->name(),
            'title'              => array_get($data, 'title', $slug),
            'title_display_name' => array_get($entry->fieldset()->fields(), 'title.display', t('title')),
            'uuid'               => $id,
            'uri'                => $entry->uri(),
            'url'                => $entry->url(),
            'slug'               => $slug,
            'status'             => $status,
            'locale'             => $locale,
            'is_default_locale'  => $entry->isDefaultLocale(),
            'locales'            => $this->getLocales($id)
        ]);
    }


    /**
     * Create a new taxonomy
     *
     * @param string $group_name  The group the taxonomy will belong to
     * @return \Illuminate\View\View
     */
    public function createTaxonomy($group_name)
    {
        $this->authorize("taxonomies:$group_name:create");

        if (! $group = Taxonomy::whereHandle($group_name)) {
            return redirect(route('collections'))->withErrors("Taxonomy [$group->path()] doesn't exist.");
        }

        $fieldset = $group->fieldset()->name();

        $data = $this->populateWithBlanks($fieldset);

        $title = translate(
            'cp.create_taxonomy_term',
            ['term' => str_singular(Stringy::toTitleCase($group->title()))]
        );

        $extra = [
            'group' => $group_name,
            'route' => $group->route()
        ];

        return view('publish', [
            'extra'             => $extra,
            'is_new'            => true,
            'content_data'      => $data,
            'content_type'      => 'taxonomy',
            'fieldset'          => $fieldset,
            'title'             => $title,
            'uuid'              => null,
            'url'               => null,
            'uri'               => null,
            'slug'              => null,
            'status'            => true,
            'locale'            => default_locale(),
            'is_default_locale' => true,
            'locales'           => $this->getLocales()
        ]);
    }

    /**
     * Edit an existing taxonomy term
     *
     * @param string $taxonomy
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function editTaxonomy($taxonomy, $slug)
    {
        $this->authorize("taxonomies:$taxonomy:edit");

        $locale = $this->request->query('locale', site_locale());

        if (! $term = Term::whereSlug($slug, $taxonomy)) {
            return redirect()->route('term.show', $taxonomy)->withErrors('No taxonomy found.');
        }

        $id = $term->id();

        $term = $term->in($locale)->get();

        $status = $term->published();

        $extra = [
            'taxonomy' => $taxonomy,
            'default_slug' => $slug
        ];

        $data = $this->populateWithBlanks($term);

        return view('publish', [
            'extra'             => $extra,
            'is_new'            => false,
            'content_data'      => $data,
            'content_type'      => 'taxonomy',
            'fieldset'          => $term->fieldset()->name(),
            'title'             => array_get($data, 'title', $slug),
            'uuid'              => $id,
            'uri'               => $term->uri(),
            'url'               => $term->url(),
            'slug'              => $slug,
            'status'            => $status,
            'locale'            => $locale,
            'is_default_locale' => $term->isDefaultLocale(),
            'locales'           => $this->getLocales($id)
        ]);
    }

    /**
     * Edit an existing global set
     *
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function editGlobal($slug)
    {
        $this->authorize("globals:$slug:edit");

        $locale = $this->request->query('locale', site_locale());

        if (! $global = GlobalSet::whereHandle($slug)) {
            return redirect()->route('globals.index')->withErrors('No content found.');
        }

        $id = $global->id();

        $global = $global->in($locale)->get();

        $extra = [
            'default_slug' => $slug,
            'env' => datastore()->getEnvInScope('globals.'.$slug)
        ];

        $data = $this->populateWithBlanks($global);

        return view('publish', [
            'extra'             => $extra,
            'is_new'            => false,
            'content_data'      => $data,
            'content_type'      => 'global',
            'fieldset'          => $global->fieldset()->name(),
            'title'             => array_get($data, 'title', $global->slug()),
            'uuid'              => $id,
            'uri'               => null,
            'url'               => null,
            'slug'              => $slug,
            'status'            => true,
            'locale'            => $locale,
            'is_default_locale' => $global->isDefaultLocale(),
            'locales'           => $this->getLocales($id)
        ]);
    }

    /**
     * Get locales and their links
     *
     * @param string|null $uuid
     * @return array
     */
    private function getLocales($uuid = null)
    {
        $locales = [];

        foreach (Config::getLocales() as $locale) {
            $url = $this->request->url();

            if ($locale !== Config::getDefaultLocale()) {
                $url .= '?locale=' . $locale;
            }

            $has_content = false;
            if ($uuid) {
                $has_content = Content::find($uuid)->hasLocale($locale);
            }

            $locales[] = [
                'name'        => $locale,
                'label'       => Config::getLocaleName($locale),
                'url'         => $url,
                'is_active'   => $locale === $this->request->query('locale', Config::getDefaultLocale()),
                'has_content' => $has_content
            ];
        }

        return $locales;
    }

    /**
     * Save a page.
     *
     * The POST request from /cp/publish
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save()
    {
        $content_type = $this->request->input('type');

        if ($content_type == 'page') {
            $publisher = app('Statamic\CP\Publish\PagePublisher');
        } elseif ($content_type == 'entry') {
            $publisher = app('Statamic\CP\Publish\EntryPublisher');
        } elseif ($content_type == 'taxonomy') {
            $publisher = app('Statamic\CP\Publish\TaxonomyPublisher');
        } elseif ($content_type == 'global') {
            $publisher = app('Statamic\CP\Publish\GlobalsPublisher');
        } elseif ($content_type == 'user') {
            $publisher = app('Statamic\CP\Publish\UserPublisher');
        }

        try {
            $this->content = $publisher->publish();
        } catch (PublishException $e) {
            return [
                'success' => false,
                'errors' => $e->getErrors()
            ];
        }

        $this->success(translate('cp.thing_saved', ['thing' => ucwords($content_type)]));

        return [
            'success' => true,
            'redirect' => $this->redirectSave()->getTargetUrl()
        ];
    }

    /**
     * Return the redirect after the page has been saved
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectSave()
    {
        $locale = $this->request->input('locale');
        $localized = $locale !== default_locale();
        $continue = $this->request->input('continue', false);

        if ($this->content instanceof \Statamic\Contracts\Data\Pages\Page) {
            $params = ['url' => ltrim($this->content->url(), '/')];

            if ($localized) {
                $params['locale'] = $locale;
            }

            $redirect = ($continue) ? route('page.edit', $params) : route('pages');

        } elseif ($this->content instanceof \Statamic\Contracts\Data\Taxonomies\Term) {
            $group = $this->content->taxonomyName();
            $slug = $this->content->slug();
            $params = compact('group', 'slug');

            if ($localized) {
                $params['locale'] = $locale;
            }

            $redirect = ($continue) ? route('term.edit', $params) : route('terms.show', $group);

        } elseif ($this->content instanceof \Statamic\Contracts\Data\Entries\Entry) {
            $collection = $this->content->collectionName();
            $slug = $this->content->slug();
            $params = compact('collection', 'slug');

            if ($localized) {
                $params['locale'] = $locale;
            }

            $redirect = ($continue) ? route('entry.edit', $params) : route('entries.show', $collection);

        } elseif ($this->content instanceof \Statamic\Contracts\Data\Globals\GlobalSet) {
            $slug = $this->content->slug();
            $params = compact('slug');

            if ($localized) {
                $params['locale'] = $locale;
            }


            $redirect = ($continue) ? route('globals.edit', $params) : route('globals');

        } elseif ($this->content instanceof \Statamic\Contracts\Data\Users\User) {
            $continue_route = route('user.edit', $this->content->username());

            if ($continue) {
                $redirect = $continue_route;

            } else {
                $redirect = route('users');

                // If they havent chose to continue, but they dont have manage
                // permissions, just keep them on this page.
                $user = \Statamic\API\User::getCurrent();
                if ($this->content === $user && !$user->hasPermission('user:manage')) {
                    $redirect = $continue_route;
                }
            }
        }

        // Maintain any query parameters that were added
        if ($referrer_params = array_get(parse_url($this->request->header('referer')), 'query')) {
            parse_str($referrer_params, $query);

            // However, don't maintain these query parameters
            unset($query['slug'], $query['locale'], $query['fieldset']);

            if ($query) {
                $glue = strpos($redirect, '?') ? '&' : '?';
                $redirect .= $glue . http_build_query($query);
            }
        }

        return redirect($redirect);
    }

    /**
     * Create the data array, populating it with blank values for all fields in
     * the fieldset, then overriding with the actual data where applicable.
     *
     * @param string|\Statamic\Data\Content $arg Either a content object, or the name of a fieldset.
     * @return array
     */
    private function populateWithBlanks($arg)
    {
        // Get a fieldset and data
        if ($arg instanceof \Statamic\Contracts\Data\Content\Content) {
            $fieldset = $arg->fieldset();
            $data = $arg->processedData();
        } else {
            $fieldset = Fieldset::get($arg);
            $data = [];
        }

        // This will be the "merged" fieldset, built up from any partials.
        $merged_fieldset = [];

        // Get the fieldtypes
        $fieldtypes = collect($fieldset->fieldtypes());

        // Merge any fields from nested fieldsets (only a single level - @todo: recursion)
        $partials = collect();
        $fieldtypes->each(function ($ft) use ($partials, &$merged_fieldset) {
            if ($ft->getAddonClassName() === 'Partial') {
                $pfs = Fieldset::get($ft->getFieldConfig('fieldset'));

                $merged_fieldset = array_merge($pfs->fields(), $merged_fieldset);

                foreach ($pfs->fieldtypes() as $f) {
                    $partials->push($f);
                }
            }
        });

        // Merge the partials and key everything by field name.
        $fieldtypes = $fieldtypes->merge($partials)->keyBy(function($ft) {
            return $ft->getName();
        });
        $merged_fieldset = array_merge($fieldset->fields(), $merged_fieldset);

        // Build up the blanks
        $blanks = [];
        foreach ($merged_fieldset as $name => $config) {
            if (! $default = array_get($config, 'default')) {
                $default = $fieldtypes->get($name)->blank();
            }

            $blanks[$name] = $default;
            if ($fieldtype = $fieldtypes->get($name)) {
                $blanks[$name] = $fieldtype->preProcess($default);
            }
        }

        return array_merge($blanks, $data);
    }
}
