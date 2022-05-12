<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Hooks;

use Craft;
use craft\elements\Entry;
use craft\events\DraftEvent;
use craft\events\ModelEvent;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\web\View;
use Gewerk\SocialMediaConnect\AssetBundle\ComposeShareAssetBundle;
use Gewerk\SocialMediaConnect\AssetBundle\EntryShareCounterAssetBundle;
use Gewerk\SocialMediaConnect\Job\PublishShare;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Record;

/**
 * Hooks and event listener related to entries
 *
 * @package Gewerk\SocialMediaConnect\Hooks
 */
class EntryHooks
{
    /**
     * Renders the compose share component inside an entry editing interface
     *
     * @param array $context
     * @return void
     */
    public static function renderComposeShare(array &$context)
    {
        // Get entry from context
        /** @var Entry */
        $entry = $context['entry'];

        // Build settings
        $settings = Json::encode([
            'entryId' => $entry->id,
            'canonicalId' => $entry->canonicalId,
            'siteId' => $entry->siteId,
            'draft' => $entry->getIsDraft() || $entry->getStatus() !== Entry::STATUS_LIVE,
        ], JSON_UNESCAPED_UNICODE);

        // Load assets
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ComposeShareAssetBundle::class);
        $view->registerJs(
            "new Craft.SocialMediaConnect.ComposeShare('smc-compose-share', {$settings});",
            View::POS_END
        );
    }

    /**
     * Renders the entry share counter component inside an entry meta interface
     *
     * @param array $context
     * @return string
     */
    public static function renderEntryShareCounter(array &$context)
    {
        // Get entry from context
        /** @var Entry */
        $entry = $context['entry'];

        // Build settings
        $settings = Json::encode([
            'entryId' => $entry->id,
            'canonicalId' => $entry->canonicalId,
            'siteId' => $entry->siteId,
        ], JSON_UNESCAPED_UNICODE);

        // Load assets
        $view = Craft::$app->getView();
        $view->registerAssetBundle(EntryShareCounterAssetBundle::class);
        $view->registerJs(
            "new Craft.SocialMediaConnect.EntryShareCounter('entry-share-counter', {$settings});",
            View::POS_END
        );

        // Get counter
        $count = SocialMediaConnect::$plugin->getShare()->getCountOfSharesByEntry($entry);

        // Render template
        return $view->renderTemplate('social-media-connect/entry-share-counter/hook.twig', [
            'count' => $count,
        ]);
    }

    /**
     * Submit share posting jobs to the queue after publishing
     *
     * @param ModelEvent $event
     * @return void
     */
    public static function submitShareJob(ModelEvent $event)
    {
        /** @var Entry $entry */
        $entry = $event->sender;

        if (ElementHelper::isDraftOrRevision($entry) || $entry->getStatus() !== Entry::STATUS_LIVE) {
            return;
        }

        // Check for any unpublished shares
        $shareService = SocialMediaConnect::$plugin->getShare();
        $unpublishedShares = $shareService->getShareBaseQuery()
            ->addSelect([
                '[[social_media_connect_accounts.name]] AS accountName'
            ])
            ->where([
                '[[social_media_connect_shares.entryId]]' => $entry->id,
                '[[social_media_connect_shares.siteId]]' => $entry->siteId,
                '[[social_media_connect_shares.publishWithEntry]]' => true,
                '[[social_media_connect_shares.success]]' => null,
            ])
            ->all();

        foreach ($unpublishedShares as $unpublishedShare) {
            Craft::$app->getQueue()->push(new PublishShare([
                'shareId' => $unpublishedShare['id'],
                'accountName' => $unpublishedShare['accountName'],
            ]));
        }
    }

    /**
     * Move shares from a draft to its canonical version before merging draft
     *
     * @param DraftEvent $event
     * @return void
     */
    public static function moveDraftShares(DraftEvent $event)
    {
        // Move an draft shares to canonical element
        Db::update(
            Record\Share::tableName(),
            ['entryId' => $event->draft->getCanonicalId()],
            ['entryId' => $event->draft->getId()]
        );
    }
}
