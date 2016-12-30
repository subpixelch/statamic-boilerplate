<?php

namespace Statamic\Data\Content;

use Statamic\API\Str;
use Statamic\API\Helper;
use Statamic\API\Term;
use Statamic\Data\DataCollection;

/**
 * A collection of Content data types. (Pages and Entries)
 */
class ContentCollection extends DataCollection
{
    /**
     * Swap the locale of the items
     *
     * @param string $locale
     * @return static
     */
    public function localize($locale)
    {
        $items = new static($this->items);

        // Filter out items that don't have the requested locale if it was prefixed with 'only'.
        // For example 'only fr' would remove any items that don't have a french locale.
        if (Str::startsWith($locale, 'only ')) {
            $locale = explode('only ', $locale)[1];

            $items = $this->filter(function ($item) use ($locale) {
                return $item->hasLocale($locale);
            });
        }

        return $items->map(function ($item) use ($locale) {
            return $item->in($locale)->get();
        });
    }

    /**
     * Filter by folder(s)
     *
     * @param mixed $folders  A folder, pipe-delimited list of folders, or array of folders to pull from
     * @return static
     */
    public function from($folders = null)
    {
        $folders = Helper::normalizeArguments(func_get_args());

        return new static($this->filter(function($item) use ($folders) {
            foreach ($folders as $folder) {
                if (strpos($folder, '/') === 0) {
                    $folder = substr($folder, 1);
                }

                if ($folder === "*" || $folder === "/*") {
                    // include all
                    return true;
                    break;
                } elseif (substr($folder, -1) === "*") {
                    // wildcard check
                    if (strpos($item->folder(), substr($folder, 0, -1)) === 0) {
                        return true;
                        break;
                    }
                } elseif ($folder == $item->folder()) {
                    return true;
                    break;
                }
            }

            return false;
        }));
    }

    /**
     * Filter by entries
     *
     * @return static
     */
    public function entries()
    {
        return new static ($this->filter(function($item) {
            return ($item instanceof Entry);
        }));
    }

    /**
     * Filter by pages
     *
     * @return static
     */
    public function pages()
    {
        return new static ($this->filter(function($item) {
            return ($item instanceof Page);
        }));
    }

    /**
     * Remove unpublished content
     *
     * @return static
     */
    public function removeUnpublished()
    {
        return new static ($this->filter(function($item) {
            return (method_exists($item, 'published')) ? $item->published() : true;
        }));
    }

    /**
     * Removes content with a date in the future
     *
     * @return static
     */
    public function removeFuture()
    {
        return $this;
    }

    /**
     * Removes content with a date in the past
     *
     * @return static
     */
    public function removePast()
    {
        return $this;
    }

    /**
     * Removes content before whose date is before a given date
     *
     * @param mixed $before
     * @return static
     */
    public function removeBefore($before)
    {
        return $this;
    }

    /**
     * Removes content before whose date is after a given date
     *
     * @param mixed $after
     * @return static
     */
    public function removeAfter($after)
    {
        return $this;
    }

    /**
     * Removes any content that doesn't belong to the given taxonomy
     *
     * @param string $id
     * @return static
     */
    public function filterByTaxonomy($id)
    {
        $group = Term::find($id)->taxonomyName();

        return $this->filter(function($entry) use ($id, $group) {
            $taxonomies = Helper::ensureArray($entry->get($group, []));

            return in_array($id, $taxonomies);
        });
    }
}
