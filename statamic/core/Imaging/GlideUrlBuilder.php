<?php

namespace Statamic\Imaging;

use Exception;
use Statamic\API\Str;
use Statamic\API\URL;
use League\Glide\Urls\UrlBuilderFactory;

class GlideUrlBuilder extends ImageUrlBuilder
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Build the URL
     *
     * @param \Statamic\Contracts\Assets\Asset|string $item
     * @param array                                   $params
     * @param string|null                             $filename
     * @return string
     * @throws \Exception
     */
    public function build($item, $params, $filename = null)
    {
        $this->item = $item;

        switch ($this->itemType()) {
            case 'asset':
                $path = 'id/' . $this->item->id();
                break;
            case 'id':
                $path = 'id/' . $this->item;
                break;
            case 'url':
                $path = 'http/' . base64_encode($item);
                break;
            case 'path':
                $path = $this->item;
                break;
            default:
                throw new Exception("Cannot build a Glide URL without a URL, path, or asset.");
        }

        $builder = UrlBuilderFactory::create($this->options['route'], $this->options['key']);

        if ($filename) {
            $path .= Str::ensureLeft(URL::encode($filename), '/');
        }

        return URL::prependSiteRoot($builder->getUrl($path, $params));
    }
}
