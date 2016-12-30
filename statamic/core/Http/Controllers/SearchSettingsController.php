<?php

namespace Statamic\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\API\Config;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\Events\SearchSettingsUpdated;
use Statamic\API\Search;

class SearchSettingsController extends CpController
{
    /**
     * Display the settings page for editing the search settings.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit()
    {
        return view('settings.edit', [
            'title'        => t('settings_search'),
            'slug'         => 'search',
            'content_data' => $this->searchConfig(),
            'content_type' => 'settings',
            'fieldset'     => 'settings.search',
            'extra' => [
                'env' => datastore()->getEnvInScope('settings.search')
            ],
        ]);
    }

    /**
     * Update the search data.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $settings = $this->saveSettings($request->fields);

        event(new SearchSettingsUpdated($settings));

        $this->success('Settings updated');

        return [
            'success' => true,
            'redirect' => route('settings.search.edit')
        ];
    }

    /**
     * Save the settings to the file.
     *
     * @param  array  $attributes
     * @return array
     */
    private function saveSettings(array $attributes)
    {
        $yaml = YAML::dump($attributes);

        File::put(settings_path('search.yaml'), $yaml);

        return $attributes;
    }

    /**
     * Get the config and override the algolia searchable attributes with
     * the one fetched from the API.
     *
     * @return array
     */
    private function searchConfig()
    {
        $config = Config::get('search');

        list($index, $key, $appid) = [
            $config['default_index'],
            $config['algolia_api_key'],
            $config['algolia_app_id']
        ];

        // Just some quick validation to avoid breaking for now.
        if ($key && $appid) {
            $config['algolia_searchable_attributes'] = Search::in($index)->searchableAttributes();
        }

        return $config;
    }
}
