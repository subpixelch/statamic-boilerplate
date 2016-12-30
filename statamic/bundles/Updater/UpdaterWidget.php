<?php

namespace Statamic\Addons\Updater;

use GuzzleHttp\Client;
use Statamic\API\Content;
use Statamic\Extend\Widget;

class UpdaterWidget extends Widget
{
    public function html()
    {
        return $this->view('widget')->withUpdates($this->getUpdateCount());
    }

    private function getUpdateCount()
    {
        $client = new Client();
        $response = $client->get('https://outpost.statamic.com/v2/changelog');
        $releases = collect(json_decode($response->getBody()));

        return $releases->filter(function ($item) {
            return version_compare($item->name, STATAMIC_VERSION, '>');
        })->count();
    }
}
