<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Controller;

use Craft;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Gewerk\SocialMediaConnect\Exception\CallbackException;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Record\Token as TokenRecord;
use yii\db\Exception;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Actions to manage social media accounts
 *
 * @package Gewerk\SocialMediaConnect\Controller
 */
class AccountsController extends Controller
{
    /**
     * Connects an account
     *
     * @param string $provider
     * @return Response
     */
    public function actionConnect(string $provider): Response
    {
        // Get provider
        $provider = SocialMediaConnect::$plugin->getProviders()->getProviderByHandle($provider);
        Craft::$app->getSession()->set('provider', $provider->getHandle());

        if (!$provider) {
            throw new NotFoundHttpException();
        }

        // Save return to URL to session
        $redirectUri = Craft::$app->getRequest()->getValidatedBodyParam('redirectUri');
        Craft::$app->session->set('redirectUri', $redirectUri);

        // Handle connect
        return $provider->handleConnect($this->request);
    }

    /**
     * Handels the callback from the connect action
     *
     * @return Response
     */
    public function actionCallback(): Response
    {
        // Get provider
        $providerHandle = Craft::$app->getSession()->get('provider');
        $provider = SocialMediaConnect::$plugin->getProviders()->getProviderByHandle($providerHandle);

        if (!$provider) {
            throw new NotFoundHttpException();
        }

        // Get redirect URI from session
        $redirectUri = Craft::$app->session->get('redirectUri', null) ?:
            UrlHelper::cpUrl('social-media-connect/accounts');

        // Get token from provider
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Save token
            $token = $provider->handleCallback($this->request);
            $token->providerId = $provider->id;

            $tokenRecord = TokenRecord::findOne([
                'providerId' => $token->providerId,
                'identifier' => $token->identifier,
            ]) ?? new TokenRecord([
                'providerId' => $token->providerId,
                'identifier' => $token->identifier,
            ]);

            $tokenRecord->token = $token->token;
            $tokenRecord->refreshToken = $token->refreshToken;
            $tokenRecord->expiryDate = Db::prepareDateForDb($token->expiryDate);
            $tokenRecord->scopes = implode(',', $token->scopes);
            $tokenRecord->save();

            $token->id = $tokenRecord->id;

            // Handle accounts
            $provider->getAccounts($token);

            $transaction->commit();
        } catch (CallbackException $e) {
            $this->setFailFlash(Craft::t('social-media-connect', 'Connecting {provider} failed: {message}', [
                'provider' => $provider->getName(),
                'message' => $e->getMessage(),
            ]));

            $transaction->rollBack();

            if (!$e->getRedirect()) {
                $redirectUri = null;
            }
        } catch (Exception $e) {
            $transaction->rollBack();
        } finally {
            return $redirectUri ? $this->redirect($redirectUri, 302) : null;
        }
    }
}
