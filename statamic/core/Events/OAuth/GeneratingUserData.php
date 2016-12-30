<?php

namespace Statamic\Events\OAuth;

use Statamic\Events\Event;

class GeneratingUserData extends Event
{
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}