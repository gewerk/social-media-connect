<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\OAuth2;

use Gewerk\SocialMediaConnect\Model\Token;

/**
 * Providers which need refreshing of access tokens should implement
 * this interface.
 *
 * @package Gewerk\SocialMediaConnect\Provider\OAuth2
 */
interface SupportsTokenRefreshingInterface
{
    /**
     * Refreshes the access token
     *
     * @param Token $token
     * @return Token
     */
    public function refreshToken(Token $token): Token;
}
