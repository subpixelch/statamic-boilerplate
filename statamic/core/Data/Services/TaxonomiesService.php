<?php

namespace Statamic\Data\Services;

class TaxonomiesService extends BaseService
{
    /**
     * The repo key
     *
     * @var string
     */
    protected $repo = 'taxonomies';

    /**
     * {@inheritdoc}
     */
    public function handle($handle)
    {
        return $this->repo()->getItem($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function handles($handles)
    {
        return $this->repo()->getItems()->filter(function ($taxonomy, $key) use ($handles) {
            return in_array($key, $handles);
        });
    }
}