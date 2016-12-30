<?php

namespace Statamic\Addons\Taxonomy;

use Statamic\API\Term;
use Statamic\Addons\Relate\RelateFieldtype;

class TaxonomyFieldtype extends RelateFieldtype
{
    public function process($data)
    {
        $data = collect(parent::process($data))->map(function ($item) {
            if (Term::exists($item)) {
                return $item;
            }

            $term = Term::create(slugify($item))
                ->taxonomy($this->get('taxonomy'))
                ->with(['title' => $item])
                ->ensureId()
                ->save();

            return $term->id();
        })->all();

        return parent::process($data);
    }
}
