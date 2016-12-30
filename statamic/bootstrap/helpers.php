<?php

use Carbon\Carbon;
use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\File;
use Statamic\API\Path;
use Statamic\API\Config;
use Michelf\SmartyPants;
use Statamic\Extend\API;
use Statamic\Extend\Addon;
use Michelf\MarkdownExtra;
use Illuminate\Support\Arr;
use Statamic\Data\DataCollection;
use Illuminate\Support\Debug\Dumper;
use Stringy\StaticStringy as Stringy;
use Statamic\View\Blade\Modifier as BladeModifier;

if (! function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" or "colon" notation.
     *
     * @param  array  $array
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        if ($key) {
            $key = str_replace(':', '.', $key);
        }

        return Arr::get($array, $key, $default);
    }
}

/**
 * Reindex an array so unnamed keys are named
 *
 * @param array $array
 * @return mixed
 */
function array_reindex($array)
{
    if (array_values($array) === $array) {
        $array = array_flip($array);
    }

    return $array;
}

/**
 * Filtering a array by its keys using a callback.
 *
 * @param $array array The array to filter
 * @param $callback Callback The filter callback, that will get the key as first argument.
 *
 * @return array The remaining key => value combinations from $array.
 */
function array_filter_key(array $array, $callback)
{
    $matchedKeys = array_filter(array_keys($array), $callback);

    return array_intersect_key($array, array_flip($matchedKeys));
}

function translate($id, array $parameters = [])
{
    return trans($id, $parameters);
}

function translate_choice($id, $number, array $parameters = [])
{
    return trans_choice($id, $number, $parameters);
}

function bool_str($bool)
{
    return ((bool) $bool) ? 'true' : 'false';
}

/**
 * Gets or sets the site locale
 *
 * @param string|null $locale
 * @return string
 */
function site_locale($locale = null)
{
    if ($locale) {
        return config(['app.locale' => $locale]);
    }

    return config('app.locale');
}

/**
 * Gets the site's default locale
 *
 * @return string
 */
function default_locale()
{
    if (env('APP_ENV') === 'testing') {
        return 'en';
    }

    return Config::getDefaultLocale();
}

function cp_route($route, $params = [])
{
    if (! CP_ROUTE) {
        return null;
    }

    return route($route, $params);
}

function cp_resource_url($url)
{
    return URL::assemble(SITE_ROOT, pathinfo(request()->getScriptName())['basename'], RESOURCES_ROUTE, 'cp', $url);
}

function path($from, $extra = null)
{
    return Path::tidy($from . '/' . $extra);
}

function statamic_path($path = null)
{
    return path(APP, $path);
}

/**
 * Path from the root filesystem location
 * (ie. the folder above `statamic`)
 *
 * @param string|null $path
 * @return string
 */
function root_path($path = null)
{
    return statamic_path('../' . $path);
}

/**
 * Path from webroot
 *
 * @param string|null $path
 * @return string
 */
function webroot_path($path = null)
{
    return path(realpath(STATAMIC_ROOT), $path);
}

function site_path($path = null)
{
    return path(statamic_path('../site'), $path);
}

function local_path($path = null)
{
    return path(statamic_path('../local'), $path);
}

function bundles_path($path = null)
{
    return path(statamic_path('bundles'), $path);
}

function addons_path($path = null)
{
    return path(site_path('addons'), $path);
}

function settings_path($path = null)
{
    return path(site_path('settings'), $path);
}

function site_storage_path($path = null)
{
    return path(site_path('storage'), $path);
}

function cache_path($path = null)
{
    return path(local_path('cache'), $path);
}

function logs_path($path = null)
{
    return path(local_path('logs'), $path);
}

function temp_path($path = null)
{
    return path(local_path('temp'), $path);
}

function carbon($value)
{
    if (! $value instanceof Carbon) {
        $value = (is_numeric($value)) ? Carbon::createFromTimestamp($value) : Carbon::parse($value);
    }

    return $value;
}

/**
 * @return \Statamic\Repositories\AddonRepository
 */
function addon_repo()
{
    return app('Statamic\Repositories\AddonRepository');
}

/**
 * @return \Statamic\DataStore
 */
function datastore()
{
    return app('Statamic\DataStore');
}

/**
 * @return \Statamic\Extend\Management\Loader
 */
function resource_loader()
{
    return app('Statamic\Extend\Management\Loader');
}

/**
 * @param array $value
 * @return \Statamic\Assets\AssetCollection
 */
function collect_assets($value = [])
{
    return new \Statamic\Assets\AssetCollection($value);
}

/**
 * @param array $value
 * @return \Statamic\FileCollection;
 */
function collect_files($value = [])
{
    return new \Statamic\FileCollection($value);
}

/**
 * @param array $value
 * @return \Statamic\Data\Content\ContentCollection
 */
function collect_content($value = [])
{
    return new \Statamic\Data\Content\ContentCollection($value);
}

/**
 * @param array $value
 * @return \Statamic\Data\Pages\PageCollection
 */
function collect_pages($value = [])
{
    return new \Statamic\Data\Pages\PageCollection($value);
}

/**
 * @param array $value
 * @return \Statamic\Data\Entries\EntryCollection
 */
function collect_entries($value = [])
{
    return new \Statamic\Data\Entries\EntryCollection($value);
}

/**
 * @param array $value
 * @return \Statamic\Data\Taxonomies\TermCollection
 */
function collect_terms($value = [])
{
    return new \Statamic\Data\Taxonomies\TermCollection($value);
}

/**
 * @param array $value
 * @return \Statamic\Data\Globals\GlobalCollection
 */
function collect_globals($value = [])
{
    return new \Statamic\Data\Globals\GlobalCollection($value);
}

/**
 * @param array $value
 * @return \Statamic\Data\Users\UserCollection
 */
function collect_users($value = [])
{
    return new \Statamic\Data\Users\UserCollection($value);
}

/**
 * Gets an addon's API class if it exists, or creates a temporary generic addon class.
 *
 * @param string $addon
 * @return Addon|API
 */
function addon($addon)
{
    try {
        $addon = app("Statamic\\Addons\\{$addon}\\{$addon}API");
    } catch (ReflectionException $e) {
        $addon = new Addon($addon);
    }

    return $addon;
}

/**
 * @return \Statamic\CP\Navigation\Navigation
 */
function nav()
{
    return app('Statamic\CP\Navigation\Nav');
}

/**
 * Instantiate a custom collection filter instance
 *
 * @param string                        $plugin_name
 * @param \Statamic\Data\DataCollection $collection
 * @param array                         $context
 * @param array                         $params
 * @return \Statamic\Extend\FilterInterface
 */
function collection_filter($plugin_name, DataCollection $collection, $context = [], $params = [])
{
    $class = Str::studly($plugin_name);

    $class = "Statamic\\Addons\\{$class}\\{$class}Filter";

    return new $class($collection, $context, $params);
}

/**
 * Convert a width to a bootstrap column class
 *
 * @param int $width Percentage as a width
 * @return string
 */
function col_class($width)
{
    return 'col-md-' . round($width / 8.333);
}

/**
 * SVG helper
 *
 * @param string $src Path to svg in the cp image directory
 * @return string
 */
function svg($src)
{
    $svg = File::get(statamic_path('resources/img/' . $src . '.svg'));

    return Stringy::collapseWhitespace($svg);
}

/**
 * Output an "active" class if a url matches
 *
 * @param string $url
 * @return string
 */
function active_for($url)
{
    $url = ltrim(URL::makeRelative($url), '/');

    return app()->request->is($url) ? 'selected' : '';
}

/**
 * Check whether the nav link is active
 *
 * @param string $url
 * @return string
 */
function nav_is($url)
{
    $url = URL::makeRelative($url);
    $url = ltrim(URL::removeSiteRoot($url), '/');
    $url = preg_replace('/^index\.php\//', '', $url);

    return request()->is($url . '*');
}

/**
 * Make sure a URL /looks/like/this
 *
 * @param string $url Any given URL
 * @return string
 */
function format_url($url)
{
    return '/' . trim($url, '/');
}

/**
 * Parse string with basic Markdown
 *
 * @param $content
 * @return mixed
 */
function markdown($content)
{
    return MarkdownExtra::defaultTransform($content);
}

/**
 * Parse string with basic Textile
 *
 * @param $content
 * @return string
 */
function textile($content)
{
    $parser = new \Netcarver\Textile\Parser();

    return $parser
        ->setDocumentType('html5')
        ->parse($content);
}

/**
 * Shorthand for translate()
 *
 * @param string $var
 * @param array  $params
 * @return string
 */
function t($var, $params = [])
{
    return translate('cp.'.$var, $params);
}

/**
 * Turns a string into a slug
 *
 * @param string $var
 * @return string
 */
function slugify($value)
{
    return Stringy::slugify($value);
}

/**
 * Parse string with SmartyPants
 *
 * @param $content
 * @param int $behavior
 * @return mixed
 */
function smartypants($content, $behavior = null)
{
    if ($behavior) {
        return SmartyPants::defaultTransform($content, $behavior);
    }

    return SmartyPants::defaultTransform($content);
}

/**
 * Returns a real boolean from a string based boolean
 *
 * @param string $value
 * @return bool
 */
function bool($value)
{
    return ! in_array(strtolower($value), ['no', 'false', '0', '', '-1']);
}

/**
 * Return a real integer from a string based integer
 *
 * @param string $value
 * @return int
 */
function int($value)
{
    return intval($value);
}

function d()
{
    array_map(function ($x) {
        (new Dumper)->dump($x);
    }, func_get_args());

}

/**
 * Return a gravatar image
 *
 * @param  string  $email
 * @param  integer $size
 * @return string
 */
function gravatar($email, $size = null)
{
    $url = "https://www.gravatar.com/avatar/" . e(md5(strtolower($email)));

    if ($size) {
        $url .= '?s=' . $size;
    }

    return $url;
}

function format_input_options($options)
{
    $formatted_options = [];

    foreach ($options as $key => $text) {
        if ($options === array_values($options)) {
            $formatted_options[] = ['value' => $text, 'text' => $text];
        } else {
            $formatted_options[] = ['value' => $key, 'text' => $text];
        }
    }

    return $formatted_options;
}

function format_update($string)
{
    $string = markdown($string);
    $string = Str::replace($string, '[new]', '<span class="label label-info">New</span>');
    $string = Str::replace($string, '[fix]', '<span class="label label-success">Fix</span>');
    $string = Str::replace($string, '[break]', '<span class="label label-danger">Break</span>');

    return $string;
}

/**
 * Start modifying a value within a Blade template
 *
 * @param  mixed $value
 * @return Statamic\View\Blade\Modifier
 */
function modify($value)
{
    return \Statamic\View\Modify::value($value);
}

/**
 * Detect whether we're running `php please addons:refresh` in the console
 *
 * @return bool
 */
function refreshing_addons()
{
    return app()->runningInConsole() && array_get($_SERVER, 'argv.1') === 'addons:refresh';
}

/**
 * The middleware names applied to CP routes.
 *
 * @return array
 */
function cp_middleware()
{
    return ['locale', 'outpost'];
}
