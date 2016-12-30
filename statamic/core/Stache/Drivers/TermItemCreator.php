<?php

namespace Statamic\Stache\Drivers;

use Statamic\API\Path;
use Statamic\API\YAML;
use Statamic\API\Term;
use Statamic\Stache\Stache;
use Illuminate\Support\Collection;

class TermItemCreator
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
            $taxonomy = explode('/', $path)[1];
            $item = $this->createTerm($contents, $path, $taxonomy);
            return compact('item', 'path', 'taxonomy');
        })->values()->groupBy('taxonomy');
    }

    private function createTerm($contents, $path, $taxonomy)
    {
        return Term::create(pathinfo(Path::clean($path))['filename'])
            ->taxonomy($taxonomy)
            ->with(YAML::parse($contents))
            ->published(app('Statamic\Contracts\Data\Content\StatusParser')->entryPublished($path))
            ->order(app('Statamic\Contracts\Data\Content\OrderParser')->getEntryOrder($path))
            ->get();
    }
}