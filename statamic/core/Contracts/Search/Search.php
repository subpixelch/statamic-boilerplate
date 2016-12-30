<?php

namespace Statamic\Contracts\Search;

interface Search
{
    /**
     * Perform a search
     *
     * @param  string $query String to search
     * @param  array|null $fields Fields to search in, or null to search all fields
     * @return array
     */
    public function search($query, $fields = null);

    /**
     * Get a search index
     *
     * @param  string $index Name of the index
     * @return Index
     */
    public function index($index);

    /**
     * Update a search index
     *
     * @param  string $index Name of the index
     * @return void
     */
    public function update($index = null);
}
