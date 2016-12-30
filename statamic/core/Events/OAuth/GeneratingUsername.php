<?php

namespace Statamic\Events\OAuth;

use Statamic\Events\Event;

class GeneratingUsername extends Event
{
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}