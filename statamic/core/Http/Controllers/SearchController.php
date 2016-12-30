<?php

namespace Statamic\Http\Controllers;

use Statamic\API\Config;
use Statamic\API\Folder;
use Statamic\API\Search;
use Statamic\API\Content;
use Illuminate\Http\Request;

class SearchController extends CpController
{
    /**
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * The view for /cp/search
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return 'Todo. For now, go to /cp/search/perform?query=your+term';
    }

    /**
     * Update the search index
     */
    public function update()
    {
        Search::update();

        return 'Index updated.';
    }

    /**
     * Search for a term
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function search(Request $request)
    {
        // Update the index if it doesn't already exist
        if (! $this->isIndexed()) {
            Search::update();
        }

        $query = $request->query('q');

        // Zend should search for specific fields. Other drivers - ie. Algolia - should just search their
        // default methods. If Algolia has searchable atttributes defined, it'll use those. Otherwise,
        // it'll search all indexed attributes. Either way, Algolia will be much faster than Zend.
        $fields = (Config::get('search.driver') === 'zend') ? ['title', 'url'] : null;

        if (strlen($query) >= 3) {
            $query = $query . '*'; // Wildcard some of the things;
        }

        $results = Search::get($query, $fields);

        foreach ($results as $key => $result) {
            $id = $result['id'];
            $content = Content::find($id)->toArray();
            $content['search_score'] = $result['_score'];
            $results[$key] = $content;
        }

        return $results;
    }

    /**
     * Determine if an index has already been created.
     *
     * Assumes it has if you aren't using the default driver.
     * People setting up a custom driver would likely also index it.
     *
     * @todo: Check it anyway.
     * @return boolean
     */
    private function isIndexed()
    {
        if (Config::get('search.driver') !== 'zend') {
            return true;
        }

        return Folder::exists(storage_path('search/'.Config::get('search.default_index')));
    }
}
