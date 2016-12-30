<?php

namespace Statamic;

use Log;
use GuzzleHttp\Client;
use Statamic\API\Cache;
use Statamic\API\Config;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;

class Outpost
{
    /**
     * The URL of the Outpost
     */
    const ENDPOINT = 'https://outpost.statamic.com/v2/query';

    /**
     * Where the cached response will be stored
     */
    const RESPONSE_CACHE_KEY = 'outpost_response';

    /**
     * @var Illuminate\Http\Request
     */
    private $request;

    /**
     * @var array
     */
    private $response;

    /**
     * Create a new Outpost instance
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Radio into the Outpost
     *
     * @return array
     */
    public function radio()
    {
        if ($this->hasCachedResponse()) {
            return $this->response = $this->getCachedResponse();
        }

        $this->performRequest();

        $this->cacheResponse();
        return $this->response;
    }

    /**
     * Is the site's license key valid?
     *
     * @return boolean
     */
    public function isLicenseValid()
    {
        return array_get($this->response, 'license_valid');
    }

    /**
     * Is the site on a publicly accessible domain?
     *
     * @return boolean
     */
    public function isOnPublicDomain()
    {
        return array_get($this->response, 'public_domain');
    }

    /**
     * Is the site on their designated licensed domain?
     *
     * @return boolean
     */
    public function isOnCorrectDomain()
    {
        return array_get($this->response, 'correct_domain');
    }

    /**
     * Is there an update available?
     *
     * @return boolean
     */
    public function isUpdateAvailable()
    {
        return array_get($this->response, 'update_available');
    }

    /**
     * What's the latest version?
     *
     * @return string
     */
    public function getLatestVersion()
    {
        return array_get($this->response, 'latest_version');
    }

    /**
     * Perform the request to the Outpost
     *
     * @return void
     */
    private function performRequest()
    {
        // Set up a default response in case of a failed communication with the Outpost.
        $response = $this->getDefaultResponse();

        try {
            $client = new Client;
            $response = $client->request('POST', self::ENDPOINT, ['json' => $this->getPayload()]);
            $response = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::notice("Couldn't reach the Statamic Outpost.");
        } catch (Exception $e) {
            Log::error("Ran into an issue when contacting the Statamic Outpost.");
        }

        $this->response = $response;
    }

    /**
     * Cache the response. An hour feels about right.
     *
     * @return void
     */
    private function cacheResponse()
    {
        Cache::put(self::RESPONSE_CACHE_KEY, $this->response, 60);
    }

    /**
     * Check if a response has been cached, and whether it should be used.
     *
     * @return boolean
     */
    private function hasCachedResponse()
    {
        // No cache? That was simple.
        if (! Cache::has(self::RESPONSE_CACHE_KEY)) {
            return false;
        }

        // Changing the license key essentially invalidates the cache
        if (Config::get('system.license_key') !== array_get($this->getCachedResponse(), 'license_key')) {
            return false;
        }

        return true;
    }

    /**
     * Get the cached response
     *
     * @return array
     */
    private function getCachedResponse()
    {
        return Cache::get(self::RESPONSE_CACHE_KEY);
    }

    /**
     * Get a default response to use if the request can't be made
     *
     * @return array
     */
    private function getDefaultResponse()
    {
        return [
            'license_key'      => Config::get('system.license_key'),
            'latest_version'   => STATAMIC_VERSION,
            'update_available' => false,
            'license_valid'    => false
        ];
    }

    /**
     * Get the payload to be sent to the Outpost
     *
     * @return array
     */
    private function getPayload()
    {
        return [
            'license_key' => Config::get('system.license_key'),
            'version'     => STATAMIC_VERSION,
            'php_version' => PHP_VERSION,
            'request'     => [
                'domain'  => request()->server('HTTP_HOST'),
                'ip'      => request()->ip(),
                'port'    => request()->getPort()
            ]
        ];
    }
}
