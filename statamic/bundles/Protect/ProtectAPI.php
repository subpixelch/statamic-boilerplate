<?php

namespace Statamic\Addons\Protect;

use Statamic\Extend\API;

class ProtectAPI extends API
{
    public function protect($url, $scheme)
    {
        (new ProtectorManager($url, $scheme))->protect();
    }
}
