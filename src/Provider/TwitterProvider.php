<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use Craft;
use craft\helpers\Cp;
use DateTime;
use Gewerk\SocialMediaConnect\Collection\AccountCollection;
use Gewerk\SocialMediaConnect\Collection\PostCollection;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Element\Post;
use Gewerk\SocialMediaConnect\Exception\TokenRefreshException;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\Capability\PullPostsCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\OAuth2\AbstractProvider;
use Gewerk\SocialMediaConnect\Provider\OAuth2\SupportsTokenRefreshingInterface;
use Gewerk\SocialMediaConnect\Provider\PostPayload\TwitterPostPayload;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;
use Gewerk\SocialMediaConnect\Provider\Share\TwitterShare;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Throwable;
use Twitter\Text\Autolink;
use Twitter\Text\Extractor;

/**
 * Twitter provider
 *
 * @package Gewerk\SocialMediaConnect\Provider
 */
class TwitterProvider extends AbstractProvider implements ComposingCapabilityInterface, PullPostsCapabilityInterface, SupportsTokenRefreshingInterface
{
    const TWITTER_API_ENDPOINT = 'api.twitter.com';
    const TWITTER_API_VERSION = '2';

    /**
     * @var bool Enable posting
     */
    public $enablePosting = false;

    /**
     * @var GuzzleClient|null
     */
    private $_guzzle = null;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Twitter';
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return '@social-media-connect/resources/icons/twitter.svg';
    }

    /**
     * @inheritdoc
     */
    public function getProviderClass(): string
    {
        return GenericProvider::class;
    }

    /**
     * @inheritdoc
     */
    public static function getShareModelClass(): string
    {
        return TwitterShare::class;
    }

    /**
     * @inheritdoc
     */
    public static function getPostPayloadClass(): string
    {
        return TwitterPostPayload::class;
    }

    /**
     * @inheritdoc
     */
    public function getScopes(): array
    {
        $scopes = ['offline.access', 'tweet.read', 'users.read'];

        if ($this->enablePosting) {
            $scopes[] = 'tweet.write';
        }

        return $scopes;
    }

    /**
     * @inheritdoc
     */
    public function getProviderOptions(): array
    {
        $options = parent::getProviderOptions();
        $options['scopeSeparator'] = ' ';
        $options['urlAuthorize'] = 'https://twitter.com/i/oauth2/authorize?code_challenge=challenge&code_challenge_method=plain';
        $options['urlAccessToken'] = 'https://api.twitter.com/2/oauth2/token?code_verifier=challenge';
        $options['urlResourceOwnerDetails'] = '';

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function supportsComposing(Account $account): bool
    {
        return $this->enablePosting && in_array('tweet.write', $account->getToken()->scopes);
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
    public function refreshToken(Token $token): Token
    {
        try {
            /** @var GenericProvider */
            $provider = $this->getConfiguredProvider();
            $refreshedAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $token->refreshToken,
                'client_id' => $this->clientId,
            ]);

            $token->token = $refreshedAccessToken->getToken();
            $token->expiryDate = $refreshedAccessToken->getExpires() ? DateTime::createFromFormat('U', $refreshedAccessToken->getExpires()) : null;
            $token->refreshToken = $refreshedAccessToken->getRefreshToken();

            return $token;
        } catch (IdentityProviderException $e) {
            throw new TokenRefreshException($token, 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getComposingHtml(AbstractShare $share): string
    {
        /** @var TwitterShare $share */
        $fields = [];
        $fields[] = Cp::textareaFieldHtml([
            'label' => Craft::t('social-media-connect', 'Message'),
            'name' => 'message',
            'value' => $share->message,
            'errors' => $share->getErrors('message'),
        ]);

        // Render an open graph preview
        $view = Craft::$app->getView();
        $metadata = Plugin::$plugin->getShare()->getMetadataFromEntryPreview($share->getEntry());
        $fields[] = $view->renderTemplate(
            'social-media-connect/link-preview/twitter',
            [
                'domain' => parse_url($metadata->url, PHP_URL_HOST),
                'metadata' => $metadata,
            ],
        );

        return implode("\n", $fields);
    }

    /**
     * @inheritdoc
     */
    public function publishShare(AbstractShare $share): AbstractShare
    {
        $account = $share->getAccount();
        $token = $account->getToken();

        // Refresh access token if it expired
        if ($token->isExpired()) {
            try {
                Plugin::$plugin->getTokens()->refreshToken($token);
            } catch (Throwable $e) {
                Craft::warning($e->getTraceAsString());
            }
        }

        // Publish to Twitter
        try {
            /** @var TwitterShare $share */
            $entry = $share->getEntry();
            $response = $this->getGuzzleClient()->post('tweets', [
                'json' => [
                    'text' => $share->message . ' ' . $entry->getUrl(),
                ],
                'headers' => [
                    'Authorization' => "Bearer {$token->token}",
                ],
            ]);

            // Get body from request
            $body = json_decode((string) $response->getBody(), true);

            // Save information to record
            $share->success = true;
            $share->response = $body;
            $share->postedAt = new DateTime();
            $share->postUrl = sprintf('https://twitter.com/%s/status/%s', $account->handle, $body['data']['id']);
        } catch (RequestException $e) {
            $error = json_decode((string) $e->getResponse()->getBody(), true);

            // Save error to record
            $share->success = false;
            $share->response = $error;
            $share->addError('success', $error['title']);
        }

        return $share;
    }

    /**
     * @inheritdoc
     */
    public function getShareErrorMessage(AbstractShare $share): string
    {
        return $share->response['title'] ?? 'Unknown error';
    }

    /**
     * @inheritdoc
     */
    public function defineShareAttributes(AbstractShare $share): array
    {
        return [
            'message' => Craft::t('social-media-connect', 'Message'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getShareAttributeHtml(AbstractShare $share, string $attribute): string
    {
        switch ($attribute) {
            case 'message':
                return $share->message;

            default:
                return '';
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
    public function getIdentifier(Token $token): string
    {
        // Query current token user
        $response = $this->getGuzzleClient()->get('users/me', [
            'headers' => [
                'Authorization' => "Bearer {$token->token}",
            ],
        ]);

        // Get body from request
        $userDetails = json_decode((string) $response->getBody(), true);

        return $userDetails['data']['id'];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $settings = [];

        $settings[] = Cp::lightswitchFieldHtml([
            'label' => Craft::t('social-media-connect', 'Enable posting'),
            'instructions' => Craft::t('social-media-connect', 'Allow posting from entry editor'),
            'name' => 'enablePosting',
            'on' => $this->enablePosting,
        ]);

        return parent::getSettingsHtml() . "\n" . implode("\n", $settings);
    }

    /**
     * @inheritdoc
     */
    public function getAccounts(Token $token): AccountCollection
    {
        // Refresh access token if it expired
        if ($token->isExpired()) {
            try {
                Plugin::$plugin->getTokens()->refreshToken($token);
            } catch (Throwable $e) {
                Craft::warning($e->getTraceAsString());
            }
        }

        // Query current token user
        $response = $this->getGuzzleClient()->get('users/me', [
            'headers' => [
                'Authorization' => "Bearer {$token->token}",
            ],
        ]);

        // Get body from request
        $userDetails = json_decode((string) $response->getBody(), true);

        // Find or create account
        $account = Account::findOneOrCreate([
            'tokenId' => $token->id,
            'identifier' => $userDetails['data']['id'],
        ]);

        // Update account
        $account->name = $userDetails['data']['name'];
        $account->handle = $userDetails['data']['username'];
        $account->lastRefreshedAt = new DateTime();
        $account->connectorId = Craft::$app->getUser()->getIdentity()->getId();

        // Save account
        Craft::$app->getElements()->saveElement($account);

        return new AccountCollection([$account]);
    }

    /**
     * @inheritdoc
     */
    public function getPosts(Account $account, int $limit = 10): PostCollection
    {
        // Refresh access token if it expired
        $token = $account->getToken();
        if ($token->isExpired()) {
            try {
                Plugin::$plugin->getTokens()->refreshToken($token);
            } catch (Throwable $e) {
                Craft::warning($e->getTraceAsString());
            }
        }

        // Get timeline
        $response = $this->getGuzzleClient()->get("users/{$account->identifier}/tweets", [
            'headers' => [
                'Authorization' => "Bearer {$token->token}",
            ],
            'query' => [
                'exclude' => 'replies,retweets',
                'expansions' => 'attachments.media_keys',
                'max_results' => $limit,
                'media.fields' => 'alt_text,height,media_key,type,url,width',
                'tweet.fields' => 'id,text,created_at,entities,attachments',
            ],
        ]);

        // Get body from request
        $body = json_decode((string) $response->getBody(), true);
        $posts = new PostCollection();

        // Process posts
        foreach ($body['data'] as $tweet) {
            // Find or create social media post
            $post = Post::findOneOrCreate([
                'account' => $account,
                'identifier' => $tweet['id'],
            ]);

            // Set post date
            $post->type = self::getPostPayloadClass();
            $post->postedAt = DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $tweet['created_at']);
            $post->url = sprintf('https://twitter.com/%s/status/%s', $account->handle, $tweet['id']);

            /** @var TwitterPostPayload */
            $payload = $post->getPayload();
            $payload->type = 'text';
            $payload->text = $this->autolinkTweet($tweet['text'], $tweet['entities'] ?? []);

            // Save post
            if (Craft::$app->getElements()->saveElement($post)) {
                $posts->add($post);
            }
        }

        return $posts;
    }

    /**
     * Autolinks all entities in a tweet
     *
     * @param string $tweet
     * @param array $entities
     * @return string
     */
    private function autolinkTweet(string $tweet, array $entities): string
    {
        // Merge all entity types in one list
        $entities = call_user_func_array('array_merge', $entities);
        $entities = array_map(fn ($entity) => array_merge($entity, [
            'indices' => [$entity['start'], $entity['end']],
        ]), $entities);
        $entities = Extractor::create()->removeOverlappingEntities($entities);

        // Apply entities to tweet
        return (new Autolink())->autoLinkEntities($tweet, $entities);
    }

    /**
     * Returns a Guzzle client with Graph API as base URI
     *
     * @return GuzzleClient
     */
    private function getGuzzleClient(): GuzzleClient
    {
        // Create guzzle client for Twitter API
        if ($this->_guzzle === null) {
            $this->_guzzle = Craft::createGuzzleClient([
                'base_uri' => sprintf('https://%s/%s/', self::TWITTER_API_ENDPOINT, self::TWITTER_API_VERSION),
                'allow_redirects' => [
                    'track_redirects' => true,
                ],
            ]);
        }

        return $this->_guzzle;
    }
}
