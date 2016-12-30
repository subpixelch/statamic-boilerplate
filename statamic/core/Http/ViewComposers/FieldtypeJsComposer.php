<?php

namespace Statamic\Http\ViewComposers;

use Statamic\API\Str;
use Statamic\API\File;
use Statamic\API\Path;
use Statamic\API\Config;
use Illuminate\Contracts\View\View;

class FieldtypeJsComposer
{
    public function compose(View $view)
    {
        $view->with('fieldtype_js', $this->fieldtypeJs());
    }

    private function fieldtypeJs()
    {
        // Don't bother doing anything on the login screen.
        if (\Route::current() && \Route::current()->getName() === 'login') {
            return '';
        }

        $fieldtypes = addon_repo()->filter('Fieldtype.php')->getFiles();

        $defaults = [];

        $str = '';

        foreach ($fieldtypes as $path) {
            $dir = pathinfo($path)['dirname'];

            // Add the default value to the array
            $name = explode('/', $dir)[2];
            $fieldtype = app('Statamic\CP\FieldtypeFactory')->create($name);
            $defaults[$fieldtype->snakeName()] = $fieldtype->blank();

            if (File::exists(Path::assemble($dir, 'resources/assets/js/fieldtype.js'))) {
                $str .= $fieldtype->js->tag('fieldtype');
            }
        }

        return '<script>Statamic.fieldtypeDefaults = '.json_encode($defaults).';</script>' . $str . $this->redactor();
    }

    private function redactor()
    {
        $str = '<script>Statamic.redactorSettings = ';

        $configs = collect(Config::get('system.redactor', []))->keyBy('name')->map(function ($config) {
            return $config['settings'];
        })->all();

        $str .= json_encode($configs);

        $str .= ';</script>';

        return $str;
    }
}
