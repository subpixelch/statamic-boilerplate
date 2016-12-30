<?php

namespace Statamic\Addons\Glide;

use Statamic\API\Str;
use Statamic\API\Asset;
use Statamic\API\Image;
use League\Glide\Server;
use Statamic\Extend\Tags;
use Statamic\Imaging\ImageGenerator;

class GlideTags extends Tags
{
    /**
     * Maps to {{ glide:[field] }}
     *
     * Where `field` is the variable containing the image ID
     *
     * @param  $method
     * @param  $args
     * @return string
     */
    public function __call($method, $args)
    {
        $tag = explode(':', $this->tag, 2)[1];

        $item = array_get($this->context, $tag);

        return $this->output($this->generateGlideUrl($item));
    }

    /**
     * Maps to {{ glide }}
     *
     * Alternate syntax, where you pass the ID or path directly as a parameter or tag pair content
     *
     * @return string
     */
    public function index()
    {
        $item = ($this->content)
            ? $this->parse([])
            : $this->get(['src', 'id', 'path']);

        return $this->output($this->generateGlideUrl($item));
    }

    /**
     * Maps to {{ glide:generate }} ... {{ /glide:generate }}
     *
     * Generates the image and makes variables available within the pair.
     *
     * @return string
     */
    public function generate()
    {
        $item = $this->get(['src', 'id', 'path']);

        $url = $this->generateGlideUrl($item);

        $path = $this->generateImage($item);

        list($width, $height) = getimagesize($this->getServer()->getCache()->getAdapter()->getPathPrefix().$path);

        return $this->parse(
            compact('url', 'width', 'height')
        );
    }

    /**
     * Generate the image
     *
     * @param string $item  Either a path or an asset ID
     * @return string       Path to the generated image
     */
    private function generateImage($item)
    {
        $params = $this->getGlideParams($item);

        return (Str::isUrl($item))
            ? $this->getGenerator()->generateByPath($item, $params)
            : $this->getGenerator()->generateByAsset(Asset::find($item), $params);
    }

    /**
     * Output the tag
     *
     * @param string $url
     * @return string
     */
    private function output($url)
    {
        if ($this->getBool('tag')) {
            return "<img src=\"$url\" alt=\"{$this->get('alt')}\" />";
        }

        return $url;
    }

    /**
     * The URL generation
     *
     * @param  string $item  Either the ID or path of the image.
     * @return string
     */
    private function generateGlideUrl($item)
    {
        try {
            return $this->getManipulator($item)->build();
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    /**
     * Get the raw Glide parameters
     *
     * @param string|null $item
     * @return array
     */
    private function getGlideParams($item = null)
    {
        return $this->getManipulator($item)->getParams();
    }

    /**
     * Get the image manipulator with the parameters added to it
     *
     * @param string|null $item
     * @return \Statamic\Imaging\GlideImageManipulator
     */
    private function getManipulator($item = null)
    {
        $manipulator = Image::manipulate($item);

        $this->getManipulationParams()->each(function ($value, $param) use ($manipulator) {
            $manipulator->$param($value);
        });

        return $manipulator;
    }

    /**
     * Get the tag parameters applicable to image manipulation
     *
     * @return \Illuminate\Support\Collection
     */
    private function getManipulationParams()
    {
        $params = collect();

        foreach ($this->parameters as $param => $value) {
            if (! in_array($param, ['src', 'id', 'path', 'tag', 'alt'])) {
                $params->put($param, $value);
            }
        }

        return $params;
    }

    /**
     * Get the image generator
     *
     * @return ImageGenerator
     */
    private function getGenerator()
    {
        return app(ImageGenerator::class);
    }

    /**
     * Get the Glide Server instance
     *
     * @return Server
     */
    private function getServer()
    {
        return app(Server::class);
    }
}
