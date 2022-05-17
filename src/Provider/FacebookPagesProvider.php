<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\Site;
use DateTime;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Element\Post;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\Capability\PullPostsCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\OAuth2\AbstractProvider;
use Gewerk\SocialMediaConnect\Provider\PostPayload\FacebookPostPayload;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;
use Gewerk\SocialMediaConnect\Provider\Share\FacebookPageShare;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Provider\AppSecretProof;
use League\OAuth2\Client\Provider\Facebook;
use VStelmakh\UrlHighlight\UrlHighlight;

/**
 * Facebook pages provider
 *
 * @package Gewerk\SocialMediaConnect\Provider
 */
class FacebookPagesProvider extends AbstractProvider implements
    ComposingCapabilityInterface,
    PullPostsCapabilityInterface
{
    protected const FACEBOOK_API_VERSION = 'v13.0';
    protected const FACEBOOK_API_ENDPOINT = 'graph.facebook.com';

    /**
     * @var bool Enable posting
     */
    public $enablePosting = false;

    /**
     * @var GuzzleClient
     */
    private $guzzle;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Facebook Pages';
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return '@social-media-connect/resources/icons/facebook.svg';
    }

    /**
     * @inheritdoc
     */
    public function getProviderClass(): string
    {
        return Facebook::class;
    }

    /**
     * @inheritdoc
     */
    public static function getShareModelClass(): string
    {
        return FacebookPageShare::class;
    }

    /**
     * @inheritdoc
     */
    public static function getPostPayloadClass(): string
    {
        return FacebookPostPayload::class;
    }

    /**
     * @inheritdoc
     */
    public function getScopes(): array
    {
        $scopes = ['public_profile', 'pages_show_list', 'pages_read_engagement'];

        if ($this->enablePosting) {
            $scopes[] = 'pages_manage_posts';
        }

        return $scopes;
    }

    /**
     * @inheritdoc
     */
    public function getProviderOptions(): array
    {
        $options = parent::getProviderOptions();
        $options['graphApiVersion'] = self::FACEBOOK_API_VERSION;

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function supportsComposing(Account $account): bool
    {
        return $this->enablePosting && in_array('pages_manage_posts', $account->getToken()->scopes);
    }

    /**
     * @inheritdoc
     */
    public function getComposingHtml(AbstractShare $share): string
    {
        /** @var FacebookPageShare $share */

        $fields = [];

        $fields[] = Cp::textareaFieldHtml([
            'label' => Craft::t('social-media-connect', 'Message'),
            'name' => 'message',
            'value' => $share->message,
            'errors' => $share->getErrors('message'),
        ]);

        // Render an open graph preview
        $view = Craft::$app->getView();
        $metadata = SocialMediaConnect::$plugin->getShare()->getMetadataFromEntryPreview($share->getEntry());
        $fields[] = $view->renderTemplate(
            'social-media-connect/link-preview/facebook',
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
        /** @var FacebookPageShare $share */

        // Publish to Facebook
        try {
            $entry = $share->getEntry();
            $account = $share->getAccount();
            $token = $account->settings['access_token'];
            $endpoint = sprintf('/%d/feed', $account->identifier);
            $appSecretProof = AppSecretProof::create($this->getClientSecret(), $token);

            $response = $this->getGuzzleClient()->post($endpoint, [
                'json' => [
                    'message' => $share->message,
                    'link' => $entry->getUrl(),
                ],
                'query' => [
                    'appsecret_proof' => $appSecretProof,
                    'access_token' => $token,
                ],
            ]);

            // Get body from request
            $node = json_decode((string) $response->getBody(), true);

            // Save information to record
            $share->success = true;
            $share->response = $node;
            $share->postedAt = new DateTime();
            $share->postUrl = sprintf('https://facebook.com/%s', $node['id']);
        } catch (RequestException $e) {
            $error = json_decode((string) $e->getResponse()->getBody(), true);

            // Save error to record
            $share->success = false;
            $share->response = $error;
            $share->addError('success', $error['error']['message']);
        }

        return $share;
    }

    /**
     * @inheritdoc
     */
    public function getShareErrorMessage(AbstractShare $share): string
    {
        return $share->response['error']['message'] ?? 'Unknown error';
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
    public function getIdentifier(Token $token): string
    {
        // Query current token user
        $appSecretProof = AppSecretProof::create($this->getClientSecret(), $token->token);
        $response = $this->getGuzzleClient()->get('me', [
            'query' => [
                'appsecret_proof' => $appSecretProof,
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
    public function handleAccounts(Site $site, Token $token): void
    {
        // Get page tokens
        $appSecretProof = AppSecretProof::create($this->getClientSecret(), $token->token);
        $response = $this->getGuzzleClient()->get('me/accounts', [
            'query' => [
                'appsecret_proof' => $appSecretProof,
                'access_token' => $token->token,
            ],
        ]);

        // Get all pages from request
        $body = json_decode((string) $response->getBody(), true);
        foreach ($body['data'] as $pageToken) {
            // Find or create account
            $account = Account::find()
                ->site($site)
                ->tokenId($token->id)
                ->identifier($pageToken['id'])
                ->trashed(null)
                ->anyStatus()
                ->one() ?? new Account([
                    'tokenId' => $token->id,
                    'identifier' => $pageToken['id'],
                    'siteId' => $site->id,
                ]);

            // Get page details
            $appSecretProof = AppSecretProof::create($this->getClientSecret(), $pageToken['access_token']);
            $response = $this->getGuzzleClient()->get($pageToken['id'], [
                'query' => [
                    'appsecret_proof' => $appSecretProof,
                    'access_token' => $pageToken['access_token'],
                    'fields' => 'name,link,picture,username',
                ],
            ]);

            // Get page details
            $pageDetails = json_decode((string) $response->getBody(), true);

            // Update account
            $account->name = $pageDetails['name'];
            $account->handle = $pageDetails['username'] ?? '-';
            $account->lastRefreshedAt = new DateTime();
            $account->connectorId = Craft::$app->getUser()->getIdentity()->getId();
            $account->settings = [
                'access_token' => $pageToken['access_token'],
            ];

            // Save account
            Craft::$app->getElements()->saveElement($account);
        }
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
        $token = $account->settings['access_token'];
        $appSecretProof = AppSecretProof::create($this->getClientSecret(), $token);
        $response = $this->getGuzzleClient()->get("{$account->identifier}/posts", [
            'query' => [
                'locale' => 'de',
                'appsecret_proof' => $appSecretProof,
                'access_token' => $token,
                'limit' => $limit,
                'fields' => implode(',', [
                    'id',
                    'created_time',
                    'message',
                    'message_tags',
                    'permalink_url',
                    'status_type',
                    'timeline_visibility',
                    'via',
                    'full_picture',
                    'attachments{description,description_tags,media,media_type,unshimmed_url,title,type}',
                    'story',
                    'story_tags',
                    'privacy',
                ]),
            ],
        ]);

        // Get body
        $feed = json_decode((string) $response->getBody(), true);
        $urlHighlight = new UrlHighlight();

        // Process posts
        foreach ($feed['data'] as $feedPost) {
            // Allowlist for post types
            $allowedTypes = ['added_photos', 'added_video', 'mobile_status_update', 'shared_story'];
            if (!in_array($feedPost['status_type'], $allowedTypes)) {
                continue;
            }

            // Don't import non-public posts
            if ($feedPost['privacy']['value'] !== 'EVERYONE') {
                continue;
            }

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
                    'siteId' => $account->siteId,
                ]);

            // Set post data
            $post->postedAt = DateTime::createFromFormat(DateTime::ISO8601, $feedPost['created_time']);
            $post->url = $feedPost['permalink_url'];

            /** @var FacebookPostPayload */
            $payload = $post->getPayload();

            // Message empty? Use story
            $payload->text = $urlHighlight->highlightUrls(
                $this->parseFacebookTextTags($feedPost['message'] ?? '', $feedPost['message_tags'] ?? [])
            );

            if (empty($post->text) && isset($feedPost['story'])) {
                $payload->text = $this->parseFacebookTextTags($feedPost['story'], $feedPost['story_tags'] ?? []);
            }

            // Get image
            $media = ArrayHelper::getValue($feedPost, 'attachments.data', []);
            if (isset($media[0])) {
                // Set type
                $payload->type = 'text';

                // Is link?
                if ($media[0]['media_type'] === 'link' && isset($media[0]['unshimmed_url'])) {
                    $payload->linkUrl = $media[0]['unshimmed_url'];
                    $payload->linkTitle = $media[0]['title'];
                }

                // Is video?
                if ($media[0]['media_type'] === 'video' && isset($media[0]['unshimmed_url'])) {
                    $payload->type = 'video';
                    $payload->videoUrl = $media[0]['unshimmed_url'];
                }

                // Get image from attachment
                $payload->imageUrl = ArrayHelper::getValue($media[0], 'media.image.src', null);
                $payload->imageAlt = ArrayHelper::getValue($media[0], 'media.image.alt', null);

                // Set type for image
                if ($payload->imageUrl) {
                    if ($media[0]['media_type'] === 'photo') {
                        $payload->type = 'image';
                    }

                    // Set type for gallery
                    if ($media[0]['media_type'] === 'album') {
                        $payload->type = 'gallery';
                    }
                }
            }

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
     * Returns a Guzzle client with Graph API as base URI
     *
     * @return GuzzleClient
     */
    private function getGuzzleClient(): GuzzleClient
    {
        // Create guzzle client for Facebook API
        if ($this->guzzle === null) {
            $this->guzzle = Craft::createGuzzleClient([
                'base_uri' => sprintf('https://%s/%s/', self::FACEBOOK_API_ENDPOINT, self::FACEBOOK_API_VERSION),
                'allow_redirects' => [
                    'track_redirects' => true,
                ],
            ]);
        }

        return $this->guzzle;
    }

    /**
     * Parses Facebook tags in a text
     *
     * @param string $input
     * @param array $tags
     * @return string
     */
    private function parseFacebookTextTags(string $input, ?array $tags = [])
    {
        $offsetBuffer = 0;

        if (!is_array($tags)) {
            return $input;
        }

        usort($tags, fn ($a, $b) => $a['offset'] <=> $b['offset']);

        foreach ($tags as $tag) {
            $link = Html::a($tag['name'], sprintf('https://facebook.com/%s', $tag['id']));
            $input = $this->replace($input, $link, $tag['offset'] + $offsetBuffer, $tag['length']);
            $offsetBuffer += strlen($link) - $tag['length'];
        }

        return $input;
    }

    /**
     * UTF-8 safe replacement
     *
     * @param string $original
     * @param string $replacement
     * @param int $position
     * @param int $length
     * @return string
     */
    private function replace($original, $replacement, $position, $length)
    {
        $startString = mb_substr($original, 0, $position, 'UTF-8');
        $endString = mb_substr($original, $position + $length, mb_strlen($original), 'UTF-8');
        $out = $startString . $replacement . $endString;

        return $out;
    }
}
