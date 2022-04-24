<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\Request;
use DateTime;
use Gewerk\SocialMediaConnect\Collection\AccountCollection;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Exception\TokenRefreshException;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Provider\OAuth2\AbstractProvider;
use Gewerk\SocialMediaConnect\Provider\OAuth2\SupportsTokenRefreshingInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\OAuth2\Client\Provider\Exception\InstagramIdentityProviderException;
use League\OAuth2\Client\Provider\Instagram;
use League\OAuth2\Client\Token\AccessTokenInterface;

class InstagramProvider extends AbstractProvider implements SupportsTokenRefreshingInterface
{
    const INSTAGRAM_API_ENDPOINT = 'graph.instagram.com';

    /**
     * @var GuzzleClient|null
     */
    private $_guzzle = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Instagram';
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return '@social-media-connect/resources/icons/instagram.svg';
    }

    /**
     * @inheritdoc
     */
    public function getProviderClass(): string
    {
        return Instagram::class;
    }

    /**
     * @inheritdoc
     */
    public function getScopes(): array
    {
        return ['user_profile', 'user_media'];
    }

    /**
     * @inheritdoc
     */
    public function getAccessToken(Request $request): AccessTokenInterface
    {
        /** @var Instagram */
        $provider = $this->getConfiguredProvider();

        // Get short lived access token
        $token = parent::getAccessToken($request);

        // Get long lived access token
        return $provider->getLongLivedAccessToken($token);
    }

    /**
     * @inheritdoc
     */
    public function refreshToken(Token $token): AccessTokenInterface
    {
        try {
            /** @var Instagram */
            $provider = $this->getConfiguredProvider();

            return $provider->getRefreshedAccessToken($token->accessToken);
        } catch (InstagramIdentityProviderException $e) {
            throw new TokenRefreshException($token, 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function handleCallback(Request $request): Token
    {
        $token = parent::handleCallback($request);
        $token->expiryDate = new DateTime('+60 days');

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $settings = [];

        if (!$this->isNew) {
            $settings[] = Cp::textFieldHtml([
                'label' => Craft::t('social-media-connect', 'Deauthorize callback URL'),
                'instructions' => Craft::t('social-media-connect', 'Callback URL if an user deauthorizes the provider'),
                'readonly' => true,
                'value' => UrlHelper::actionUrl('social-media-connect/accounts/deauthorize', [
                    'provider' => $this->handle,
                ]),
            ]);

            $settings[] = Cp::textFieldHtml([
                'label' => Craft::t('social-media-connect', 'Data Deletion Request URL'),
                'instructions' => Craft::t('social-media-connect', 'Callback URL if an user requests deleting their data'),
                'readonly' => true,
                'value' => UrlHelper::actionUrl('social-media-connect/accounts/data-deletion-request', [
                    'provider' => $this->handle,
                ]),
            ]);
        }

        return parent::getSettingsHtml() . "\n" . implode("\n", $settings);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(Token $token): string
    {
        // Query current token user
        $response = $this->getGuzzleClient()->get('me', [
            'query' => [
                'access_token' => $token->token,
                'fields' => 'id',
            ],
        ]);

        // Get body from request
        $userDetails = json_decode((string) $response->getBody(), true);

        return $userDetails['id'];
    }

    /**
     * @inheritdoc
     */
    public function getAccounts(Token $token): AccountCollection
    {
        // Query current token user
        $response = $this->getGuzzleClient()->get('me', [
            'query' => [
                'access_token' => $token->token,
                'fields' => 'id,username',
            ],
        ]);

        // Get body from request
        $userDetails = json_decode((string) $response->getBody(), true);

        // Find or create account
        $account = Account::findOneOrCreate([
            'tokenId' => $token->id,
            'identifier' => $userDetails['id'],
        ]);

        // Update account
        $account->name = $userDetails['username'];
        $account->handle = $userDetails['username'];
        $account->lastRefreshedAt = new DateTime();
        $account->connectorId = Craft::$app->getUser()->getIdentity()->getId();

        // Save account
        Craft::$app->getElements()->saveElement($account);

        return new AccountCollection([$account]);
    }

    /**
     * Returns a Guzzle client with Graph API as base URI
     *
     * @return GuzzleClient
     */
    private function getGuzzleClient(): GuzzleClient
    {
        // Create guzzle client for Facebook API
        if ($this->_guzzle === null) {
            $this->_guzzle = Craft::createGuzzleClient([
                'base_uri' => sprintf('https://%s/', self::INSTAGRAM_API_ENDPOINT),
            ]);
        }

        return $this->_guzzle;
    }
}
