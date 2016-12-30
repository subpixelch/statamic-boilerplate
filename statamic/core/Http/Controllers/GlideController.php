<?php

namespace Statamic\Http\Controllers;

use Cache;
use Statamic\API\Asset;
use League\Glide\Server;
use Statamic\API\Config;
use Statamic\API\Stache;
use Illuminate\Http\Request;
use Statamic\Imaging\ImageGenerator;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureException;

class GlideController extends Controller
{
    /**
     * @var \League\Glide\Server
     */
    private $server;

    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var ImageGenerator
     */
    private $generator;

    /**
     * GlideController constructor.
     *
     * @param \League\Glide\Server     $server
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Server $server, Request $request)
    {
        $this->server = $server;
        $this->request = $request;
        $this->generator = new ImageGenerator($server);
    }

    /**
     * Generate a manipulated image by a path
     *
     * @param string $path
     * @return mixed
     */
    public function generateByPath($path)
    {
        $this->validateSignature();

        return $this->createResponse(
            $this->generator->generateByPath($path, $this->request->all())
        );
    }

    /**
     * Generate a manipulated image by a URL
     *
     * @param string $url
     * @return mixed
     */
    public function generateByUrl($url)
    {
        $this->validateSignature();

        $url = base64_decode($url);

        return $this->createResponse(
            $this->generator->generateByUrl($url, $this->request->all())
        );
    }

    /**
     * Generate a manipulated image by an asset ID
     *
     * @param string $id
     * @return mixed
     * @throws \Exception
     */
    public function generateByAsset($id)
    {
        $this->validateSignature();

        return $this->createResponse(
            $this->generator->generateByAsset($this->getAsset($id), $this->request->all())
        );
    }

    /**
     * Create a response
     *
     * @param string $path  Path of the generated image
     * @return mixed
     */
    private function createResponse($path)
    {
        $this->cachePath($path);

        return $this->server->getResponseFactory()->create($this->server->getCache(), $path);
    }

    /**
     * Cache the path of the Glide generated image so the Glide middleware can serve it faster.
     *
     * @param string $path
     * @return void
     */
    private function cachePath($path)
    {
        $key = md5($this->request->getUri());

        // Append the path to an array so they can all be cleared.
        $paths = Cache::get('glide::paths', []);
        $paths[$key] = $path;
        Cache::forever('glide::paths', $paths);

        // Save the path as its own key so it can be retrieved quickly.
        Cache::forever("glide::paths.$key", $path);
    }

    /**
     * Get an asset by ID
     *
     * @param string $id
     * @return \Statamic\Contracts\Assets\Asset
     * @throws \Exception
     */
    private function getAsset($id)
    {
        // If an asset exists, great. We're done here.
        if ($asset = Asset::find($id)) {
            return $asset;
        }

        // If it does not exist, it's possible the request arrived
        // before the Stache has had a chance to pick up the new
        // asset. We'll try to update it now just to be sure.
        Stache::update();

        if ($asset = Asset::find($id)) {
            return $asset;
        }

        // If it still doesn't exist, well then, it really doesn't exist.
        throw new \Exception("Asset with ID [$id] doesn't exist.");
    }

    /**
     * Validate the signature, if applicable
     *
     * @return void
     */
    private function validateSignature()
    {
        // If secure images aren't enabled, don't bother validating the signature.
        if (! Config::get('assets.image_manipulation_secure')) {
            return;
        }

        try {
            SignatureFactory::create(Config::getAppKey())->validateRequest($this->request->path(), $_GET);
        } catch (SignatureException $e) {
            abort(400, $e->getMessage());
        }
    }
}
