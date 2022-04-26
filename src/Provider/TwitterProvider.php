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
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\OAuth2\AbstractProvider;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;
use Gewerk\SocialMediaConnect\Provider\Share\TwitterShare;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Twitter provider
 *
 * @package Gewerk\SocialMediaConnect\Provider
 */
class TwitterProvider extends AbstractProvider implements ComposingCapabilityInterface
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
        $options['urlResourceOwnerDetails'] = 'https://api.twitter.com/1.1/account/verify_credentials';

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
        /** @var TwitterShare $share */

        // Publish to Facebook
        try {
            $entry = $share->getEntry();
            $account = $share->getAccount();
            $token = $account->getToken();
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
