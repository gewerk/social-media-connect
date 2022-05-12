<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\OAuth2;

use Craft;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\Request;
use craft\web\Response;
use DateTime;
use Gewerk\SocialMediaConnect\Exception\CallbackException;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Provider\AbstractProvider as BaseAbstractProvider;
use League\OAuth2\Client\Provider\AbstractProvider as LeagueAbstractProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Abstract OAuth2 provider
 *
 * @package Gewerk\SocialMediaConnect\Provider\OAuth2
 */
abstract class AbstractProvider extends BaseAbstractProvider implements ProviderInterface
{
    /**
     * Client ID
     *
     * @var string
     */
    public $clientId = null;

    /**
     * Client secret
     *
     * @var string
     */
    public $clientSecret = null;

    /**
     * @var LeagueAbstractProvider
     */
    protected $configuredProvider;

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $names = parent::settingsAttributes();
        $names[] = 'clientId';
        $names[] = 'clientSecret';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function getProviderOptions(): array
    {
        return [
            'clientId' => $this->getClientId(),
            'clientSecret' => $this->getClientSecret(),
            'redirectUri' => UrlHelper::cpUrl('social-media-connect/accounts/callback'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['clientId', 'clientSecret'], 'required'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguredProvider(): LeagueAbstractProvider
    {
        if (!$this->configuredProvider) {
            $leagueProvider = $this->getProviderClass();
            $settings = $this->getProviderOptions();

            $this->configuredProvider = new $leagueProvider($settings);
        }

        return $this->configuredProvider;
    }

    /**
     * @inheritdoc
     */
    public function getAuthorizationUrl($options = []): string
    {
        return $this->getConfiguredProvider()->getAuthorizationUrl($options);
    }

    /**
     * @inheritdoc
     */
    public function getAccessToken(Request $request): AccessTokenInterface
    {
        return $this->getConfiguredProvider()->getAccessToken('authorization_code', [
            'code' => $request->getRequiredQueryParam('code'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getClientId(): string
    {
        return App::parseEnv($this->clientId ?? '');
    }

    /**
     * @inheritdoc
     */
    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret ?? '');
    }

    /**
     * @inheritdoc
     */
    public function handleConnect(Request $request): Response
    {
        // Save state and provider to session
        $state = bin2hex(random_bytes(16));
        Craft::$app->getSession()->set('state', $state);

        // Get authorization url from provider
        $authorizationUrl = $this->getAuthorizationUrl([
            'state' => $state,
            'scope' => $this->getScopes(),
        ]);

        return (new Response())->redirect($authorizationUrl, 302);
    }

    /**
     * @inheritdoc
     */
    public function handleCallback(Request $request): Token
    {
        // Compare state
        $state = $request->getRequiredQueryParam('state');
        $savedState = Craft::$app->getSession()->get('state');
        Craft::$app->getSession()->remove('state');

        if ($state !== $savedState) {
            throw new CallbackException('OAuth2 state miss match');
        }

        $accessToken = $this->getAccessToken($request);

        $token = new Token();
        $token->token = $accessToken->getToken();
        $token->expiryDate = $accessToken->getExpires() ? DateTime::createFromFormat('U', $accessToken->getExpires()) : null;
        $token->refreshToken = $accessToken->getRefreshToken();
        $token->scopes = $this->getScopes();
        $token->identifier = $this->getIdentifier($token);

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $settings = [];

        $settings[] = Cp::autosuggestFieldHtml([
            'label' => Craft::t('social-media-connect', 'Client ID'),
            'instructions' => Craft::t('social-media-connect', 'The OAuth2 client key for {name}', [
                'name' => static::displayName(),
            ]),
            'name' => 'clientId',
            'required' => true,
            'suggestEnvVars' => true,
            'value' => $this->clientId,
            'errors' => $this->getErrors('clientId'),
        ]);

        $settings[] = Cp::autosuggestFieldHtml([
            'label' => Craft::t('social-media-connect', 'Client secret'),
            'instructions' => Craft::t('social-media-connect', 'The OAuth2 client secret for {name}', [
                'name' => static::displayName(),
            ]),
            'name' => 'clientSecret',
            'required' => true,
            'suggestEnvVars' => true,
            'value' => $this->clientSecret,
            'errors' => $this->getErrors('clientSecret'),
        ]);

        if (!$this->isNew) {
            $settings[] = '<hr>';
            $settings[] = Cp::textFieldHtml([
                'label' => Craft::t('social-media-connect', 'Redirect URI'),
                'instructions' => Craft::t('social-media-connect', 'Redirect URI for {name}', [
                    'name' => static::displayName(),
                ]),
                'readonly' => true,
                'value' => $this->getProviderOptions()['redirectUri'],
            ]);
        }

        return implode("\n", $settings);
    }
}
