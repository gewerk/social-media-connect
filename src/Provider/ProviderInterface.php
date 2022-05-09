<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use craft\base\SavableComponentInterface;
use craft\web\Request;
use craft\web\Response;
use Gewerk\SocialMediaConnect\Collection\AccountCollection;
use Gewerk\SocialMediaConnect\Exception\CallbackException;
use Gewerk\SocialMediaConnect\Model\Token;

/**
 * Interface for social media providers
 *
 * @package Gewerk\SocialMediaConnect\Provider
 */
interface ProviderInterface extends SavableComponentInterface
{
    /**
     * Returns the path to the providers’s SVG icon, or the actual SVG contents.
     *
     * @return string|null
     */
    public static function icon(): ?string;

    public function getName(): string;
    public function getHandle(): string;
    public function getEnabled(): bool;
    public function getSortOrder(): int;
    public function getUid(): string;
    public function getCpEditUrl(): string;

    /**
     * Handles connect requests
     *
     * @param Request $request
     * @return Response
     */
    public function handleConnect(Request $request): Response;

    /**
     * Handles callback requests
     *
     * @param Request $request
     * @return Token
     * @throws CallbackException
     */
    public function handleCallback(Request $request): Token;

    /**
     * Creates or updates accounts from an access token
     *
     * @param Token $token
     * @return AccountCollection
     */
    public function getAccounts(Token $token): AccountCollection;
}
