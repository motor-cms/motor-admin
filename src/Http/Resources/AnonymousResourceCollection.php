<?php

namespace Motor\Admin\Http\Resources;

class AnonymousResourceCollection extends BaseResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource, string $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);
    }
}
