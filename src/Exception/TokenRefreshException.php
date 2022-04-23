<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Exception;

use Gewerk\SocialMediaConnect\Model\Token;
use Throwable;

class TokenRefreshException extends SocialMediaConnectException
{
    /**
     * @var Token Token
     */
    public $token;

    /**
     * Constructs an exception for failing to refresh an access token
     *
     * @param Token $token
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct(Token $token, int $code = 0, ?Throwable $previous = null)
    {
        $this->token = $token;

        parent::__construct(
            sprintf('Refresh access token for %s failed', $this->token->identifier),
            $code,
            $previous
        );
    }

    /**
     * Returns the offending token
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }
}
