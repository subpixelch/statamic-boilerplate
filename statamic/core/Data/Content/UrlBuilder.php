<?php

namespace Statamic\Data\Content;

use Statamic\API\Str;
use Statamic\Contracts\Data\Content\UrlBuilder as UrlBuilderContract;

class UrlBuilder implements UrlBuilderContract
{
    /**
     * @var \Statamic\Contracts\Data\Entry|\Statamic\Data\Taxonomy
     */
    protected $content;

    /**
     * @param \Statamic\Contracts\Data\Entry|\Statamic\Data\Taxonomies\Term $content
     * @return $this
     * @throws \Exception
     */
    public function content($content)
    {
        if (! in_array($content->contentType(), ['entry', 'term'])) {
            throw new \Exception('Invalid content type. Must be entry or taxonomy.');
        }

        $this->content = $content;

        return $this;
    }

    /**
     * @param $route
     * @return string
     * @throws \Statamic\Exceptions\InvalidEntryTypeException
     */
    public function build($route)
    {
        // Routes can be defined as a string for just the route URL,
        // or they can be an array with a route for each locale.
        $route_url = (is_array($route)) ? $route[$this->content->locale()] : $route;

        preg_match_all('/{\s*([a-zA-Z0-9_\-]+)\s*}/', $route_url, $matches);

        $url = $route_url;

        foreach ($matches[1] as $key => $variable) {
            if ($value = $this->content->get($variable)) {
                $value = rawurlencode($value);
            } else {
                switch ($variable) {
                    case 'slug':
                        $value = $this->content->slug();
                        break;
                    case 'year':
                        $value = $this->content->date()->format('Y');
                        break;
                    case 'month':
                        $value = $this->content->date()->format('m');
                        break;
                    case 'day':
                        $value = $this->content->date()->format('d');
                        break;
                    default:
                        $value = '';
                        break;
                }
            }

            $url = str_replace($matches[0][$key], $value, $url);
        }

        return Str::ensureLeft($url, '/');
    }
}
