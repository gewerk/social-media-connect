<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Controller;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Actions for composing social media share posts
 *
 * @package Gewerk\SocialMediaConnect\Controller
 */
class ComposeController extends Controller
{
    /**
     * Posts the share
     *
     * @return Response
     */
    public function actionPostShare()
    {
        $this->requirePostRequest();

        $compose = $this->request->getBodyParam('compose');

        $share = $this->createShareFromRequest();
        $share->setAttributes($compose);

        /** @var ComposingCapabilityInterface */
        $provider = $share->getAccount()->getProvider();

        if ($share->validate()) {
            $success = true;
            $entry = $share->getEntry();
            $isDraft = $entry->getIsDraft();
            $isLive = $entry->getStatus() === Entry::STATUS_LIVE;

            // Publish directly if entry is live and not an unpublished draft
            if ($isLive && !$isDraft) {
                $share = $provider->publishShare($share);
                $success = $share->success;
            }

            if ($success) {
                SocialMediaConnect::$plugin->getShare()->saveShare($share, false);

                return $this->asJson([
                    'success' => true,
                ]);
            }
        }

        // Return namespace share compose fields from provider
        $fields = $provider->getComposingHtml($share);

        return $this->asJson([
            'success' => false,
            'error' => $share->getFirstError('success'),
            'fields' => Craft::$app->getView()->namespaceInputs($fields, 'compose'),
        ]);
    }

    /**
     * Renders all fields
     *
     * @return Response
     */
    public function actionFields()
    {
        $this->requirePostRequest();

        $share = $this->createShareFromRequest();

        /** @var ComposingCapabilityInterface */
        $provider = $share->getAccount()->getProvider();
        $fields = $provider->getComposingHtml($share);

        // Return namespace share compose fields from provider
        $view = Craft::$app->getView();
        return $this->asJson([
            'fields' => $view->namespaceInputs($fields, 'compose'),
        ]);
    }

    /**
     * Creates an share object from account by using entry ID
     *
     * @return AbstractShare
     */
    private function createShareFromRequest(): AbstractShare
    {
        $entry = Craft::$app->getElements()->getElementById(
            $this->request->getRequiredBodyParam('entryId'),
            Entry::class,
            $this->request->getRequiredBodyParam('siteId')
        );

        $account = Account::findOne([
            'id' => $this->request->getRequiredBodyParam('accountId'),
            'siteId' => $this->request->getRequiredBodyParam('siteId'),
        ]);

        if (!$entry || !$account) {
            throw new NotFoundHttpException();
        }

        $share = SocialMediaConnect::$plugin->getShare()->createShare($entry, $account);

        if (!$share) {
            throw new NotFoundHttpException();
        }

        return $share;
    }
}
