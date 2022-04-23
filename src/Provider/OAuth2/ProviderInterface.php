<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\OAuth2;

use craft\web\Request;
use Gewerk\SocialMediaConnect\Model\Token;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Interface for an OAuth2 based provider
 *
 * @package Gewerk\SocialMediaConnect\Provider\OAuth2
 */
interface ProviderInterface
{
    /**
     * Gets scopes
     *
     * @return array
     */
    public function getScopes(): array;

    /**
     * Returns the parsed client ID
     *
     * @return string
     */
    public function getClientId(): string;

    /**
     * Returns the parsed client secret
     *
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * Returns the League OAuth2 provider class
     *
     * @return string
     */
    public function getProviderClass(): string;

    /**
     * Gets provider options
     *
     * @return array
     */
    public function getProviderOptions(): array;

    /**
     * Gets a concrete League provider instance
     *
     * @return AbstractProvider
     */
    public function getConfiguredProvider(): AbstractProvider;

    /**
     * Get the URL used to authorize the token
     *
     * @param array $options
     * @return string
     */
    public function getAuthorizationUrl(array $options = []): string;

    /**
     * Returns the identifier for a token
     *
     * @param Token $token
     * @return string
     */
    public function getIdentifier(Token $token): string;

    /**
     * Gets an League access token from request
     *
     * @param Request $request
     * @return AccessTokenInterface
     */
    public function getAccessToken(Request $request): AccessTokenInterface;
}
