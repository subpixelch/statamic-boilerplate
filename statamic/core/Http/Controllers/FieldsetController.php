<?php

namespace Statamic\Http\Controllers;

use Statamic\API\Config;
use Statamic\API\Fieldset;
use Statamic\API\Folder;
use Statamic\API\Helper;
use Statamic\API\Str;
use Statamic\API\Pattern;
use Statamic\CP\FieldtypeFactory;
use Illuminate\Support\Collection;

class FieldsetController extends CpController
{
    /**
     * List all fieldsets
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data = [
            'title' => 'Fieldsets'
        ];

        return view('fieldsets.index', $data);
    }

    public function get()
    {
        $fieldsets = [];

        foreach (Fieldset::all() as $fieldset) {
            // If we've decided to omit hidden fieldsets, and this one should be
            // hidden, we'll just move right along.
            if (bool($this->request->query('hidden', true)) === false && $fieldset->hidden()) {
                continue;
            }

            $fieldsets[] = [
                'title'    => $fieldset->title(),
                'id'       => $fieldset->name(), // vue uses this as an id
                'uuid'     => $fieldset->name(), // keeping this here temporarily, just in case.
                'edit_url' => $fieldset->editUrl()
            ];
        }

        return ['columns' => ['title'], 'items' => $fieldsets];
    }

    /**
     * @param string $name
     * @return \Illuminate\View\View
     */
    public function edit($name)
    {
        $fieldset = Fieldset::get($name);

        $title = 'Editing ' . $name . '.yaml';

        return view('fieldsets.edit', compact('title', 'fieldset'));
    }

    /**
     * Delete a fieldset
     *
     * @return array
     */
    public function delete()
    {
        $ids = Helper::ensureArray($this->request->input('ids'));

        foreach ($ids as $name) {
            $fieldset = Fieldset::get($name);
            $fieldset->delete();
        }

        return ['success' => true];
    }

    public function getFieldset($fieldset)
    {
        $type = 'default';

        if (substr_count($fieldset, '.') === 2) {
            // addon fieldsets
            list($type, $addon, $name) = explode('.', $fieldset);
            $fieldset = $addon.'.'.$name;
        } elseif (substr_count($fieldset, '.') === 1) {
            // settings fieldsets
            list($type, $fieldset) = explode('.', $fieldset);
        }

        $fieldset = Fieldset::get($fieldset, $type);

        $fieldset->locale($this->request->input('locale'));

        try {
            $array = $fieldset->toArray($this->request->input('partials', true));
        } catch (\Exception $e) {
            return response(['success' => false, 'message' => $e->getMessage()], 500);
        }

        if ($fieldset->name() === 'user') {
            // If logging in using emails, make sure there is no username field.
            if (Config::get('users.login_type') === 'email') {
                $array['fields'] = collect($array['fields'])->reject(function ($field) {
                    return $field['name'] === 'username';
                })->values()->all();
            }
        }

        return $array;
    }

    public function fieldtypes()
    {
        $fieldtypes = [];

        foreach ($this->getAllFieldtypes() as $fieldtype) {
            $config = [];

            foreach ($fieldtype->getConfigFieldset()->fieldtypes() as $item) {
                $c = $item->getFieldConfig();

                // Go through each fieldtype in *its* config fieldset and process the values. SO META.
                foreach ($item->getConfigFieldset()->fieldtypes() as $field) {
                    if (! in_array($field->getName(), array_keys($c))) {
                        continue;
                    }

                    $c[$field->getName()] = $field->preProcess($c[$field->getName()]);
                }

                $c['display'] = trans("fieldtypes/{$fieldtype->snakeName()}.{$c['name']}");
                $c['instructions'] = markdown(trans("fieldtypes/{$fieldtype->snakeName()}.{$c['name']}_instruct"));

                $config[] = $c;
            }

            $fieldtypes[] = [
                'label' => $fieldtype->getAddonName(),
                'name' => $fieldtype->snakeName(),
                'canBeValidated' => $fieldtype->canBeValidated(),
                'canBeLocalized' => $fieldtype->canBeLocalized(),
                'canHaveDefault' => $fieldtype->canHaveDefault(),
                'config' => $config,
            ];
        }

        $hidden = ['replicator_sets', 'fields', 'asset_container', 'asset_folder', 'user_password',
                   'locale_settings', 'theme', 'redactor_settings', 'relate'];
        foreach ($fieldtypes as $key => $fieldtype) {
            if (in_array($fieldtype['name'], $hidden)) {
                unset($fieldtypes[$key]);
            }
        }

        return array_values($fieldtypes);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    private function getAllFieldtypes()
    {
        return collect([bundles_path(), addons_path()])->flatMap(function ($path) {
            return Folder::getFilesRecursively($path);
        })->filter(function ($path) {
            return Pattern::endsWith($path, 'Fieldtype.php');
        })->map(function ($path) {
            $name = str_replace('Fieldtype', '', basename($path, '.php'));
            return resource_loader()->loadFieldtype($name);
        })->sortBy(function ($fieldtype) {
            return $fieldtype->getAddonName();
        });
    }

    public function update($name)
    {
        $contents = $this->request->input('fieldset');

        $fieldset = $this->prepareFieldset($name, $contents);

        $fieldset->save();

        $this->success(translate('cp.fieldset_updated'));

        return [
            'success' => true,
            'redirect' => route('fieldset.edit', $fieldset->name())
        ];
    }

    private function process($fields)
    {
        // Go through each field in the fieldset
        foreach ($fields as $field_name => $field_config) {
            // Get the config fieldset for that field's fieldtype. Still with me?
            $type = $field_config['type'];
            $fieldtype = FieldtypeFactory::create($type);
            $fieldtypes = $fieldtype->getConfigFieldset()->fieldtypes();

            // Go through each fieldtype in the config fieldset and process the values.
            foreach ($fieldtypes as $field) {
                if (! in_array($field->getName(), array_keys($field_config))) {
                    continue;
                }

                $fields[$field_name][$field->getName()] = $field->process($fields[$field_name][$field->getName()]);
            }
        }

        return $fields;
    }

    public function updateLayout($fieldset)
    {
        $layout = collect($this->request->input('fields'))->keyBy('name')->toArray();

        $fieldset = Fieldset::get($fieldset);

        $contents = $fieldset->contents();

        $fields = array_get($contents, 'fields', []);

        $title_field = $fields['title'];

        $updated_fields = [];

        foreach ($layout as $name => $item) {
            $field = $fields[$name];

            if (isset($item['width'])) {
                $field['width'] = $item['width'];
            }

            $updated_fields[$name] = $field;
        }

        // Put back the title field at the front.
        $updated_fields = array_merge(['title' => $title_field], $updated_fields);

        $contents['fields'] = $updated_fields;

        $fieldset->contents($contents);

        $fieldset->save();
    }

    public function create()
    {
        return view('fieldsets.create', [
            'title' => 'Create fieldset'
        ]);
    }

    public function store()
    {
        $contents = $this->request->input('fieldset');

        $slug = $this->request->has('slug')
            ? $this->request->input('slug')
            : Str::slug(array_get($contents, 'title'), '_');

        $fieldset = $this->prepareFieldset($slug, $contents);

        $fieldset->save();

        $this->success(translate('cp.fieldset_created'));

        return [
            'success' => true,
            'redirect' => route('fieldset.edit', $fieldset->name())
        ];
    }

    private function prepareFieldset($slug, $contents)
    {
        // We need to key the array by name
        $fields = [];
        foreach ($contents['fields'] as $field) {
            $field_name = $field['name'];
            unset($field['name']);
            $fields[$field_name] = $field;
        }

        $contents['fields'] = $this->process($fields);

        $fieldset = Fieldset::create($slug, $contents);

        return $fieldset;
    }
}
