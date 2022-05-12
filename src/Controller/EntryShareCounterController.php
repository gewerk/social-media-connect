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
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Actions for displaying shares
 *
 * @package Gewerk\SocialMediaConnect\Controller
 */
class EntryShareCounterController extends Controller
{
    /**
     * Renders a list of shares
     *
     * @return Response
     */
    public function actionListShares()
    {
        $this->requirePostRequest();

        $entry = Craft::$app->getElements()->getElementById(
            (int) $this->request->getRequiredBodyParam('entryId'),
            Entry::class,
            (int) $this->request->getRequiredBodyParam('siteId')
        );

        if (!$entry) {
            throw new NotFoundHttpException();
        }

        $shares = SocialMediaConnect::$plugin->getShare()->getSharesByEntry($entry);

        return $this->asJson([
            'shares' => Craft::$app->getView()->renderTemplate(
                'social-media-connect/entry-share-counter/shares',
                [
                    'shares' => $shares,
                ],
            ),
        ]);
    }

    /**
     * Returns the current count of shares
     *
     * @return Response
     */
    public function actionShareCounter()
    {
        $this->requirePostRequest();

        $entry = Craft::$app->getElements()->getElementById(
            (int) $this->request->getRequiredBodyParam('entryId'),
            Entry::class,
            (int) $this->request->getRequiredBodyParam('siteId')
        );

        if (!$entry) {
            throw new NotFoundHttpException();
        }

        return $this->asJson([
            'count' => SocialMediaConnect::$plugin->getShare()->getCountOfSharesByEntry($entry),
        ]);
    }

    /**
     * Deletes a share
     *
     * @return Response
     */
    public function actionDeleteShare()
    {
        $this->requirePostRequest();

        $shareService = SocialMediaConnect::$plugin->getShare();
        $share = $shareService->getShareById(
            (int) $this->request->getRequiredBodyParam('shareId')
        );

        if (!$share || $share->success !== null) {
            throw new NotFoundHttpException();
        }

        $entry = $share->getEntry();
        $success = $shareService->deleteShare($share);
        $shares = $shareService->getSharesByEntry($entry);
        $count = $shareService->getCountOfSharesByEntry($entry);

        return $this->asJson([
            'success' => $success,
            'count' => $count,
            'shares' => Craft::$app->getView()->renderTemplate(
                'social-media-connect/entry-share-counter/shares',
                [
                    'shares' => $shares,
                ],
            ),
        ]);
    }
}
