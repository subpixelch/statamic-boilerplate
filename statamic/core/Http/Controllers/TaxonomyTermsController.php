<?php

namespace Statamic\Http\Controllers;

use Statamic\API\Helper;
use Statamic\API\Taxonomy;
use Statamic\API\Term;

/**
 * Controller for the taxonomies listing
 */
class TaxonomyTermsController extends CpController
{
    /**
     * List the taxonomies for a group
     *
     * @param string $group
     * @return \Illuminate\View\View
     */
    public function show($group)
    {
        $this->access("taxonomies:$group:edit");

        if (! Taxonomy::whereHandle($group)) {
            abort(404, "Taxonomy group [$group] does not exist.");
        }

        $title = Taxonomy::whereHandle($group)->title();

        return view('taxonomies.terms', [
            'title' => 'Taxonomies in ' . $title,
            'group' => $group,
            'group_title' => $title,
            'new_taxonomy_link' => route('taxonomy.create', compact('group'))
        ]);
    }

    /**
     * Get the taxonomies as JSON
     *
     * Used for injecting into the Vue components
     *
     * @param string $folder
     * @return mixed
     */
    public function get($folder)
    {
        $this->access("taxonomies:$folder:edit");
        
        $taxonomy = Taxonomy::whereHandle($folder);
        
        $terms = $taxonomy->terms()->supplement('checked', function() {
            return false;
        })->values();

        $columns = array_get($taxonomy->data(), 'columns', ['slug', 'title']);

        return [
            'items' => $terms,
            'columns' => $columns
        ];
    }

    /**
     * Delete a taxonomy
     *
     * @return array
     */
    public function delete()
    {
        $ids = Helper::ensureArray($this->request->input('ids'));

        $terms = [];

        // Gather all the terms to be deleted, but before deleting, check if
        // there are any authorization issues before attempting to continue.
        foreach ($ids as $id) {
            $term = Term::find($id);
            $terms[] = $term;

            $this->authorize("taxonomies:{$term->taxonomy()->path()}:delete");
        }

        // All good? Commence deleting.
        foreach ($terms as $term) {
            $term->delete();
        }

        return ['success' => true];
    }
}
