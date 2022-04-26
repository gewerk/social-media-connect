<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Service;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\events\DraftEvent;
use craft\events\ModelEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\web\View;
use DateTime;
use Gewerk\SocialMediaConnect\AssetBundle\ComposeShareAssetBundle;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Job\PublishShare;
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;
use Gewerk\SocialMediaConnect\Record;

/**
 * Share service
 *
 * @package Gewerk\SocialMediaConnect\Service
 */
class ShareService extends Component
{
    /**
     * Returns a share by ID
     *
     * @param int $id
     * @return AbstractShare|null
     */
    public function getShareById(int $id): ?AbstractShare
    {
        $shareRecord = $this->getShareBaseQuery()
            ->where([
                '[[social_media_connect_shares.id]]' => $id,
            ])
            ->one();

        if ($shareRecord) {
            // Resolve share type
            $shareRecord['type'] = $shareRecord['type']::getShareModelClass();

            /** @var AbstractShare $share */
            return ComponentHelper::createComponent(
                $shareRecord,
                AbstractShare::class
            );
        }

        return null;
    }

    /**
     * Inits a share by entry and account
     *
     * @param Entry $entry
     * @param Account $account
     * @return null|AbstractShare
     */
    public function createShare(Entry $entry, Account $account): ?AbstractShare
    {
        if (!$account->getSupportsComposing()) {
            return null;
        }

        /** @var ComposingCapabilityInterface */
        $provider = $account->getProvider();

        /** @var AbstractShare $share */
        $share = ComponentHelper::createComponent([
            'type' => $provider::getShareModelClass(),
        ], AbstractShare::class);

        $share->setEntry($entry);
        $share->setAccount($account);

        return $share;
    }

    /**
     * Saves a share
     *
     * @param AbstractShare $share
     * @param bool $validate
     * @return bool
     */
    public function saveShare(AbstractShare $share, bool $validate = true): bool
    {
        if ($validate && !$share->validate()) {
            return false;
        }

        if ($share->id) {
            $shareRecord = Record\Share::findOne([
                'id' => $share->id,
            ]);
        } else {
            $shareRecord = new Record\Share();
        }

        $shareRecord->entryId = $share->entryId;
        $shareRecord->siteId = $share->siteId;
        $shareRecord->accountId = $share->accountId;
        $shareRecord->success = $share->success;
        $shareRecord->response = $share->response;
        $shareRecord->postedAt = Db::prepareDateForDb($share->postedAt);
        $shareRecord->postUrl = $share->postUrl;
        $shareRecord->settings = $share->getSettings();

        return $shareRecord->save();
    }

    /**
     * Publishes shares from pending entries after going live
     *
     * @return void
     */
    public function publishSharesFromPendingEntries(): void
    {
        // Check for any unpublished shares
        $now = Db::prepareDateForDb(new DateTime());
        $unpublishedShares = $this->getShareBaseQuery()
            ->innerJoin(
                Table::ELEMENTS_SITES,
                [
                    'AND',
                    '[[elements_sites.id]] = [[social_media_connect_shares.entryId]]',
                    '[[elements_sites.siteId]] = [[social_media_connect_shares.siteId]]',
                ],
            )
            ->innerJoin(
                Table::ELEMENTS,
                '[[elements.id]] = [[elements_sites.id]]',
            )
            ->innerJoin(
                Table::ENTRIES,
                '[[entries.id]] = [[elements.id]]',
            )
            ->addSelect([
                '[[social_media_connect_accounts.name]] AS accountName'
            ])
            ->where([
                '[[social_media_connect_shares.publishWithEntry]]' => true,
                '[[social_media_connect_shares.success]]' => null,
                '[[elements.draftId]]' => null,
                '[[elements.revisionId]]' => null,
                '[[elements.enabled]]' => true,
                '[[elements_sites.enabled]]' => true,
            ])
            ->andWhere([
                'AND',
                ['<=', '[[entries.postDate]]', $now],
                [
                    'OR',
                    ['[[entries.expiryDate]]' => null],
                    ['>', '[[entries.expiryDate]]', $now],
                ],
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
     * Move shares from a draft to its canonical version
     *
     * @param DraftEvent $event
     * @return void
     */
    public function moveDraftShares(DraftEvent $event)
    {
        // Move an draft shares to canonical element
        Db::update(
            Record\Share::tableName(),
            ['entryId' => $event->draft->getCanonicalId()],
            ['entryId' => $event->draft->getId()]
        );
    }

    /**
     * Submit share posting jobs to the queue after publishing
     *
     * @param ModelEvent $event
     * @return void
     */
    public function submitShareJob(ModelEvent $event)
    {
        /** @var Entry $entry */
        $entry = $event->sender;

        if (ElementHelper::isDraftOrRevision($entry) || $entry->getStatus() !== Entry::STATUS_LIVE) {
            return;
        }

        // Check for any unpublished shares
        $unpublishedShares = $this->getShareBaseQuery()
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
     * Renders list of shares in entry context
     *
     * @param array $context
     * @return void
     */
    public function renderDetails(array &$context)
    {
        // Get entry from context
        /** @var Entry */
        $entry = $context['entry'];

        // Get accounts which support composing
        $accounts = ArrayHelper::where(
            Account::findAll(),
            'supportsComposing',
            true
        );

        // Build settings
        $settings = Json::encode([
            'entryId' => $entry->id,
            'canonicalId' => $entry->canonicalId,
            'siteId' => $entry->siteId,
            'draft' => $entry->getIsDraft() || $entry->getStatus() !== Entry::STATUS_LIVE,
            'accounts' => array_map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'provider' => $account->getProvider()->getName(),
                'icon' => Plugin::$plugin->getProviders()->getProviderIconSvg($account->getProvider()),
            ], $accounts),
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
     * Returns a base query for getting shares
     *
     * @return Query
     */
    private function getShareBaseQuery(): Query
    {
        return (new Query())
            ->from(Record\Share::tableName())
            ->select([
                '[[social_media_connect_shares.id]]',
                '[[social_media_connect_shares.entryId]]',
                '[[social_media_connect_shares.siteId]]',
                '[[social_media_connect_shares.accountId]]',
                '[[social_media_connect_shares.postAt]]',
                '[[social_media_connect_shares.postedAt]]',
                '[[social_media_connect_shares.success]]',
                '[[social_media_connect_shares.settings]]',
                '[[social_media_connect_shares.response]]',
                '[[social_media_connect_shares.postUrl]]',
                '[[social_media_connect_shares.dateCreated]]',
                '[[social_media_connect_shares.dateUpdated]]',
                '[[social_media_connect_shares.uid]]',
                '[[social_media_connect_providers.type]]',
            ])
            ->leftJoin(
                Record\Account::tableName(),
                '[[social_media_connect_accounts.id]] = [[social_media_connect_shares.accountId]]'
            )
            ->leftJoin(
                Record\Token::tableName(),
                '[[social_media_connect_tokens.id]] = [[social_media_connect_accounts.tokenId]]'
            )
            ->leftJoin(
                Record\Provider::tableName(),
                '[[social_media_connect_providers.id]] = [[social_media_connect_tokens.providerId]]'
            );
    }
}
