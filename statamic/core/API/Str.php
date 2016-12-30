<?php

namespace Statamic\API;

use Stringy\StaticStringy;

/**
 * Manipulating strings
 */
class Str extends \Illuminate\Support\Str
{
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([StaticStringy::class, $method], $parameters);
    }

    public static function studlyToSlug($string)
    {
        return Str::slug(Str::snake($string));
    }

    public static function isUrl($string)
    {
        return self::startsWith($string, ['http://', 'https://', '/']);
    }
}
