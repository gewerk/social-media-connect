<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Exception;

use Throwable;

class InvalidTokenIdException extends SocialMediaConnectException
{
    /**
     * @var mixed Token ID
     */
    public $tokenId;

    /**
     * Constructs an exception for a missing or invalid token ID
     *
     * @param mixed $tokenId
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct($tokenId, int $code = 0, ?Throwable $previous = null)
    {
        $this->tokenId = $tokenId;

        parent::__construct(
            empty($this->tokenId) ? 'Token ID is missing' : sprintf('Token ID %s is invalid', $this->tokenId),
            $code,
            $previous
        );
    }

    /**
     * Returns the offending token ID
     *
     * @return mixed
     */
    public function getTokenId()
    {
        return $this->tokenId;
    }
}
