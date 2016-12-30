<?php

namespace Statamic\Stache\Drivers;

use Statamic\API\Str;
use Statamic\API\File;
use Statamic\API\Path;
use Statamic\API\Term;
use Statamic\API\YAML;
use Statamic\API\Entry;
use Statamic\API\Config;
use Statamic\API\Helper;
use Statamic\API\Pattern;
use Statamic\Stache\Stache;
use Illuminate\Support\Collection;

class EntryItemCreator
{
    /**
     * @var \Statamic\Stache\Stache
     */
    private $stache;

    /**
     * @var Collection
     */
    private $files;

    /**
     * @var Collection
     */
    private $items;


    public function __construct(Stache $stache, $files)
    {
        $this->stache = $stache;
        $this->files = $files->first(); // default locale only
        $this->items = collect();
    }

    /**
     * Create/get all the entries sorted into collections
     *
     * @return Collection
     */
    public function create()
    {
        return $this->files->map(function ($contents, $path) {
            $collection = explode('/', $path)[1];
            $item = $this->createEntry($contents, $path, $collection);
            return compact('item', 'path', 'collection');
        })->values()->groupBy('collection');
    }

    private function createEntry($contents, $path, $collection)
    {
        $data = $this->autoTaxonomize(YAML::parse($contents), $path);

        return Entry::create(pathinfo(Path::clean($path))['filename'])
            ->collection($collection)
            ->with($data)
            ->published(app('Statamic\Contracts\Data\Content\StatusParser')->entryPublished($path))
            ->order(app('Statamic\Contracts\Data\Content\OrderParser')->getEntryOrder($path))
            ->get();
    }

    /**
     * Converts any non-ID based taxonomy terms and creates/converts them.
     *
     * @param array  $data  Data to be adjusted
     * @param string $path  Path to of the data
     * @return array        The data, which may or may not have been modified
     */
    private function autoTaxonomize($data, $path)
    {
        // If there are no taxonomy fields defined, the feature is disabled.
        if (! $taxonomy_fields = Config::get('system.auto_taxonomy_fields')) {
            return $data;
        }

        $updated = false;

        foreach ($taxonomy_fields as $field => $taxonomy) {
            // Field doesn't exist or has no value. Move along.
            if (! $field_data = array_get($data, $field)) {
                continue;
            }

            $field_data = Helper::ensureArray($field_data);

            foreach ($field_data as $term_key => $term_value) {
                if (Pattern::isUUID($term_value)) {
                    continue;
                }

                $slug = Str::slug($term_value);

                // Attempt to get an existing taxonomy by name
                if ($existing_term = Term::whereSlug($slug, $taxonomy)) {
                    // It exists. Just grab the ID.
                    $term_id = $existing_term->id();

                } else {
                    // It doesn't exist. We'll create it and add it to the cache.
                    $term_id = Helper::makeUuid();

                    $taxonomy_term = Term::create($slug)
                        ->taxonomy($taxonomy)
                        ->with(['id' => $term_id, 'title' => $term_value])
                        ->get();

                    $taxonomy_term->save();
                }

                // Now we'll swap the entered value with the ID.
                $data[$field][$term_key] = $term_id;

                // Flag as updated so we can save the file later.
                $updated = true;
            }
        }

        // If the data was updated we'll need to re-save it.
        if ($updated) {
            $content = array_get($data, 'content');
            unset($data['content']); // remove it so it doesnt get written to file
            $contents = YAML::dump($data, $content);
            File::disk('content')->put($path, $contents);
            $data['content'] = $content; // put it back in the right place
        }

        return $data;
    }
}
