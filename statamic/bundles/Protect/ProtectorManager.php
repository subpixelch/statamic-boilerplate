<?php

namespace Statamic\Addons\Protect;

use Statamic\Addons\Protect\Protectors\Protector;
use Statamic\Addons\Protect\Protectors\IpProtector;
use Statamic\Addons\Protect\Protectors\NullProtector;
use Statamic\Addons\Protect\Protectors\PasswordProtector;

class ProtectorManager
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    protected $scheme;

    /**
     * Protection classes in order of priority
     *
     * @var array
     */
    protected $protectors = [
        PasswordProtector::class,
        IpProtector::class,
        NullProtector::class
    ];

    /**
     * @param string $url
     * @param array  $scheme
     */
    public function __construct($url, array $scheme)
    {
        $this->url = $url;
        $this->scheme = $scheme;
    }

    /**
     * Provide protection
     *
     * @return void
     */
    public function protect()
    {
        $this->getProtectionProvider()->protect();
    }

    /**
     * Get the class that will provide protection
     *
     * @return Protector
     */
    protected function getProtectionProvider()
    {
        foreach ($this->protectors as $class) {
            $protector = $this->resolveProtector($class);

            if ($protector->providesProtection()) {
                return $protector;
            }
        }
    }

    /**
     * Create an instance of a protector
     *
     * @param string $class
     * @return Protector
     */
    protected function resolveProtector($class)
    {
        return new $class($this->url, $this->scheme);
    }
}