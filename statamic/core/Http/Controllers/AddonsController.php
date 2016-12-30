<?php

namespace Statamic\Http\Controllers;

use Statamic\API\URL;
use Statamic\API\Path;
use Statamic\API\YAML;
use Statamic\API\File;
use Statamic\API\Folder;
use Statamic\API\Helper;
use Statamic\API\Fieldset;
use Statamic\API\Str;
use Statamic\API\Cache;
use Statamic\API\Stache;

/**
 * Controller for the addon area
 */
class AddonsController extends CpController
{
    public function index()
    {
        return view('addons.index', [
            'title' => 'Addons'
        ]);
    }

    public function get()
    {
        $addons = [];

        $has_settings = addon_repo()->addons()->filter('settings.yaml')->getFiles()->map(function ($path) {
            return explode('/', $path)[2];
        })->all();

        foreach (addon_repo()->addons()->filter('meta.yaml')->getFiles() as $file) {
            $meta = YAML::parse(File::get($file));

            $addon = [
                'id'            => Path::directory($file),
                'name'          => array_get($meta, 'name'),
                'addon_url'     => array_get($meta, 'url'),
                'version'       => array_get($meta, 'version'),
                'developer'     => array_get($meta, 'developer'),
                'developer_url' => array_get($meta, 'developer_url'),
                'description'   => array_get($meta, 'description')
            ];

            $name = Path::folder($file);

            if (in_array($name, $has_settings)) {
                $addon['settings_url'] = '/' . URL::assemble(CP_ROUTE, 'addons', Str::studlyToSlug($name), 'settings');
            }

            $addons[] = $addon;
        }

        return [
            'columns' => ['name', 'version', 'developer', 'description'],
            'items' => $addons
        ];
    }

    public function delete()
    {
        $ids = Helper::ensureArray($this->request->input('ids'));

        foreach ($ids as $folder) {
            Folder::delete($folder);
        }

        return ['success' => true];
    }

    public function refresh()
    {
        \Artisan::call('addons:refresh');

        return back()->with('success', 'Addons refreshed.');
    }

    public function settings($addon)
    {
        $addon = Str::studly($addon);

        $path = "site//addons//$addon//settings.yaml";

        // If there's no settings fieldset, we'll error out.
        if (! File::exists($path)) {
            return redirect()->route('addons')->withErrors(['The requested addon does not have settings.']);
        }

        $data = addon($addon)->getConfig();

        $fieldset = Fieldset::get($addon.'.settings', 'addon');

        $data = $this->preProcessData($data, $fieldset);

        $data = $this->populateWithBlanks($fieldset, $data);

        return view('addons.settings', [
            'title' => $addon . ' ' . trans_choice('cp.settings', 2),
            'slug'  => $addon,
            'extra' => [
                'addon' => $addon
            ],
            'content_data' => $data,
            'content_type' => 'addon',
            'fieldset' => 'addon.'.$addon.'.settings'
        ]);
    }

    /**
     * Create the data array, populating it with blank values for all fields in
     * the fieldset, then overriding with the actual data where applicable.
     *
     * @param string $fieldset
     * @param array $data
     * @return array
     */
    private function populateWithBlanks($fieldset, $data)
    {
        // Get the fieldtypes
        $fieldtypes = collect($fieldset->fieldtypes())->keyBy(function($ft) {
            return $ft->getName();
        });

        // Build up the blanks
        $blanks = [];
        foreach ($fieldset->fields() as $name => $config) {
            $blanks[$name] = $fieldtypes->get($name)->blank();
        }

        return array_merge($blanks, $data);
    }

    private function preProcessData($data, $fieldset)
    {
        $fieldtypes = collect($fieldset->fieldtypes())->keyBy(function($fieldtype) {
            return $fieldtype->getFieldConfig('name');
        });

        foreach ($data as $field_name => $field_data) {
            if ($fieldtype = $fieldtypes->get($field_name)) {
                $data[$field_name] = $fieldtype->preProcess($field_data);
            }
        }

        return $data;
    }

    public function saveSettings($addon)
    {
        $addon = Str::studly($addon);

        $data = $this->processFields($addon.'.settings');

        $contents = YAML::dump($data);

        $file = settings_path('addons/' . Str::snake($addon) . '.yaml');
        File::put($file, $contents);

        Cache::clear();
        Stache::clear();

        $this->success('Settings updated');

        return ['success' => true, 'redirect' => route('addon.settings', Str::studlyToSlug($addon))];
    }

    private function processFields($fieldset_name)
    {
        $fieldset = Fieldset::get($fieldset_name, 'addon');
        $data = $this->request->input('fields');

        foreach ($fieldset->fieldtypes() as $field) {
            if (! in_array($field->getName(), array_keys($data))) {
                continue;
            }

            $data[$field->getName()] = $field->process($data[$field->getName()]);
        }

        // Get rid of null fields
        $data = array_filter($data, function($value) {
            return !is_null($value);
        });

        return $data;
    }
}
