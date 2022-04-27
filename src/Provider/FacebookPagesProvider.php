<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use DateTime;
use Gewerk\SocialMediaConnect\Collection\AccountCollection;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\OAuth2\AbstractProvider;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;
use Gewerk\SocialMediaConnect\Provider\Share\FacebookPageShare;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Provider\AppSecretProof;
use League\OAuth2\Client\Provider\Facebook;

class FacebookPagesProvider extends AbstractProvider implements ComposingCapabilityInterface
{
    const FACEBOOK_API_VERSION = 'v13.0';
    const FACEBOOK_API_ENDPOINT = 'graph.facebook.com';

    /**
     * @var bool Enable posting
     */
    public $enablePosting = false;

    /**
     * @var GuzzleClient
     */
    private $_guzzle;

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
        $metadata = Plugin::$plugin->getShare()->getMetadataFromEntryPreview($share->getEntry());
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
            $appSecretProof = AppSecretProof::create($this->clientSecret, $token);

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
        $appSecretProof = AppSecretProof::create($this->clientSecret, $token->token);
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
    public function getAccounts(Token $token): AccountCollection
    {
        // Get page tokens
        $appSecretProof = AppSecretProof::create($this->clientSecret, $token->token);
        $response = $this->getGuzzleClient()->get('me/accounts', [
            'query' => [
                'appsecret_proof' => $appSecretProof,
                'access_token' => $token->token,
            ],
        ]);

        // Prepare accounts collection
        $accounts = new AccountCollection();

        // Get all pages from request
        $body = json_decode((string) $response->getBody(), true);
        foreach ($body['data'] as $pageToken) {
            // Find or create account
            $account = Account::findOneOrCreate([
                'tokenId' => $token->id,
                'identifier' => $pageToken['id'],
            ]);

            // Get page details
            $appSecretProof = AppSecretProof::create($this->clientSecret, $pageToken['access_token']);
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

            // Add to collection
            $accounts->add($account);
        }

        return $accounts;
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
                'base_uri' => sprintf('https://%s/%s/', self::FACEBOOK_API_ENDPOINT, self::FACEBOOK_API_VERSION),
                'allow_redirects' => [
                    'track_redirects' => true,
                ],
            ]);
        }

        return $this->_guzzle;
    }
}
