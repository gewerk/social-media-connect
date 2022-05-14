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
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Element\Post;
use Gewerk\SocialMediaConnect\Exception\TokenRefreshException;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Provider\Capability\PullPostsCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\OAuth2\AbstractProvider;
use Gewerk\SocialMediaConnect\Provider\OAuth2\SupportsTokenRefreshingInterface;
use Gewerk\SocialMediaConnect\Provider\PostPayload\InstagramPostPayload;
use GuzzleHttp\Client as GuzzleClient;
use League\OAuth2\Client\Provider\Exception\InstagramIdentityProviderException;
use League\OAuth2\Client\Provider\Instagram;
use League\OAuth2\Client\Token\AccessTokenInterface;
use VStelmakh\UrlHighlight\UrlHighlight;

/**
 * Instagram provider
 *
 * @package Gewerk\SocialMediaConnect\Provider
 */
class InstagramProvider extends AbstractProvider implements
    SupportsTokenRefreshingInterface,
    PullPostsCapabilityInterface
{
    protected const INSTAGRAM_API_ENDPOINT = 'graph.instagram.com';

    /**
     * @var GuzzleClient|null
     */
    private $guzzle = null;

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
    public static function getPostPayloadClass(): string
    {
        return InstagramPostPayload::class;
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
    public function refreshToken(Token $token): Token
    {
        try {
            /** @var Instagram */
            $provider = $this->getConfiguredProvider();
            $refreshedAccessToken = $provider->getRefreshedAccessToken($token->token);

            $token->token = $refreshedAccessToken->getToken();
            $token->expiryDate = $refreshedAccessToken->getExpires() ?
                DateTime::createFromFormat('U', $refreshedAccessToken->getExpires()) : null;
            $token->refreshToken = $refreshedAccessToken->getRefreshToken();

            return $token;
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
                'instructions' => Craft::t(
                    'social-media-connect',
                    'Callback URL if an user requests deleting their data'
                ),
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
    public function handleAccounts(Token $token): void
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
        $account = Account::find()
            ->tokenId($token->id)
            ->identifier($userDetails['id'])
            ->trashed(null)
            ->anyStatus()
            ->one() ?? new Account([
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
    }

    /**
     * @inheritdoc
     */
    public function supportsPulling(Account $account): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function handlePosts(Account $account, int $limit = 10): void
    {
        // Get posts for this account
        $token = $account->getToken();
        $response = $this->getGuzzleClient()->get('me/media', [
            'query' => [
                'fields' => 'caption,id,media_type,media_url,permalink,thumbnail_url,timestamp',
                'limit' => $limit,
                'access_token' => $token->token,
            ],
        ]);

        // Get body
        $feed = json_decode((string) $response->getBody(), true);
        $urlHighlight = new UrlHighlight();

        // Process posts
        foreach ($feed['data'] as $feedPost) {
            // Find or create social media post
            $post = Post::find()
                ->account($account)
                ->identifier($feedPost['id'])
                ->trashed(null)
                ->anyStatus()
                ->one() ?? new Post([
                    'account' => $account,
                    'identifier' => $feedPost['id'],
                    'type' => self::getPostPayloadClass(),
                ]);

            // Set post date
            $post->postedAt = DateTime::createFromFormat(DateTime::ISO8601, $feedPost['timestamp']);
            $post->url = $feedPost['permalink'];

            /** @var InstagramPostPayload */
            $payload = $post->getPayload();
            $payload->type = $feedPost['media_type'] === 'CAROUSEL_ALBUM' ?
                'gallery' : strtolower($feedPost['media_type']);
            $payload->text = $urlHighlight->highlightUrls($feedPost['caption'] ?? '');
            $payload->videoUrl = $feedPost['media_type'] === 'VIDEO' ? $feedPost['media_url'] : null;
            $payload->imageUrl = $feedPost['thumbnail_url'] ?? $feedPost['media_url'] ?? null;

            // Save post
            Craft::$app->getElements()->saveElement($post);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPostAttributeHtml(Post $post): string
    {
        return $post->getPayload()->text;
    }

    /**
     * @inheritdoc
     */
    private function getGuzzleClient(): GuzzleClient
    {
        // Create guzzle client for Facebook API
        if ($this->guzzle === null) {
            $this->guzzle = Craft::createGuzzleClient([
                'base_uri' => sprintf('https://%s/', self::INSTAGRAM_API_ENDPOINT),
            ]);
        }

        return $this->guzzle;
    }
}
