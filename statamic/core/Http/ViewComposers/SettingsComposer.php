<?php

namespace Statamic\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use Statamic\API\Folder;

class SettingsComposer
{
    public function compose(View $view)
    {
        $settings = collect(Folder::getFilesByType(statamic_path('settings/defaults'), 'yaml'))
            ->map(function ($file) {
                return pathinfo($file)['filename'];
            })
            ->reject(function ($setting) {
                return $setting == 'services';
            });

        $view->with('settings', $settings);
    }
}
