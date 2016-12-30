<?php

namespace Statamic\Extend;

use Illuminate\Support\Collection;

abstract class Filter implements FilterInterface
{
    /**
     * Provides access to addon helper methods
     */
    use Extensible;

    /**
     * Provides access to methods for retrieving parameters
     */
    use HasParameters;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $context;

    /**
     * Create a new Filter instance
     *
     * @param \Illuminate\Support\Collection $collection
     * @param array                          $context
     * @param array                          $parameters
     */
    public function __construct(Collection $collection, array $context = [], array $parameters = [])
    {
        $this->bootstrap();
        $this->init();

        $this->collection = $collection;
        $this->context = $context;
        $this->parameters = $parameters;
    }
}
