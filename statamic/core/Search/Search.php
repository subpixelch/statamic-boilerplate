<?php

namespace Statamic\Search;

use Statamic\API\Config;
use Statamic\API\Content;
use Statamic\Events\SearchQueryPerformed;
use Mmanos\Search\Search as SearchPackage;
use Statamic\Contracts\Search\Search as SearchContract;

class Search implements SearchContract
{
    /**
     * Search package
     *
     * @var SearchPackage
     */
    private $package;

    /**
     * Create a new Search instance
     */
    public function __construct()
    {
        $this->package = new SearchPackage(Config::get('search.driver'));
    }

    /**
     * Perform a search
     *
     * @param  string $query String to search
     * @param  array|null $fields Fields to search in, or null to search all fields
     * @return array
     */
    public function search($query, $fields = null)
    {
        event(new SearchQueryPerformed($query));

        return $this->index()->search($fields, $query);
    }

    /**
     * Get a search index
     *
     * @param  string $index Name of the index
     * @return Index
     */
    public function index($index = null)
    {
        if (! $index) {
            $index = $this->getDefaultIndex();
        }

        return new Index($this->package->index($index));
    }

    /**
     * Update a search index
     *
     * @param  string $index Name of the index
     * @return void
     */
    public function update($index = null)
    {
        $index = $this->index($index);

        $index->deleteIndex();

        foreach (Content::all() as $id => $content) {
            $index->insert($id, $content->toArray());
        }
    }

    /**
     * Provide convenient access to methods on the default index
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        return call_user_func_array(array($this->index(), $method), $arguments);
    }

    /**
     * Get the name of the default search index
     *
     * @return string
     */
    protected function getDefaultIndex()
    {
        return Config::get('search.default_index');
    }
}
