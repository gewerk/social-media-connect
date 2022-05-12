<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Exception;

use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use Throwable;

/**
 * Thrown if a provider misses a capability
 *
 * @package Gewerk\SocialMediaConnect\Exception
 */
class MissingCapabilityException extends SocialMediaConnectException
{
    /**
     * @var string
     */
    public $capability;

    /**
     * @var ProviderInterface
     */
    public $provider;

    /**
     * Constructs an exception for failing to refresh an access token
     *
     * @param ProviderInterface $provider
     * @param string $capability
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct(
        ProviderInterface $provider,
        string $capability,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->provider = $provider;
        $this->capability = $capability;

        parent::__construct(
            sprintf(
                'Provider %s misses the %s capability',
                $this->provider->displayName(),
                $this->capability
            ),
            $code,
            $previous
        );
    }

    /**
     * Returns the missing capability
     *
     * @return string
     */
    public function getCapability()
    {
        return $this->capability;
    }

    /**
     * Returns the offending provider
     *
     * @return ProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
