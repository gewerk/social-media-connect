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
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use DateTime;
use Fusonic\OpenGraph\Objects\ObjectBase;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Helper\OpenGraphHelper;
use Gewerk\SocialMediaConnect\Job\PublishShare;
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
     * Get all shares by entry
     *
     * @param Entry $entry
     * @return array
     */
    public function getSharesByEntry(Entry $entry): array
    {
        $shareRecords = $this->getShareBaseQuery()
            ->where([
                '[[social_media_connect_shares.entryId]]' => array_unique([
                    $entry->canonicalId,
                    $entry->id,
                ]),
                '[[social_media_connect_shares.siteId]]' => $entry->siteId,
            ])
            ->all();

        return array_map(function ($shareRecord) {
            // Resolve share type
            $shareRecord['type'] = $shareRecord['type']::getShareModelClass();

            /** @var AbstractShare $share */
            return ComponentHelper::createComponent(
                $shareRecord,
                AbstractShare::class
            );
        }, $shareRecords);
    }

    /**
     * Get count of shares by entry
     *
     * @param Entry $entry
     * @return int
     */
    public function getCountOfSharesByEntry(Entry $entry): int
    {
        return $shareRecords = $this->getShareBaseQuery()
            ->where([
                '[[social_media_connect_shares.entryId]]' => array_unique([
                    $entry->canonicalId,
                    $entry->id,
                ]),
                '[[social_media_connect_shares.siteId]]' => $entry->siteId,
            ])
            ->count();
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
     * Deletes a share
     *
     * @param AbstractShare $share
     * @return bool
     */
    public function deleteShare(AbstractShare $share): bool
    {
        $affectedRows = Db::deleteIfExists(
            Record\Share::tableName(),
            [
                'id' => $share->id,
            ]
        );

        return $affectedRows > 0;
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
     * Returns the live metadata for an entry
     *
     * @param Entry $entry
     * @return ObjectBase|null
     */
    public function getMetadataFromEntryPreview(Entry $entry): ?ObjectBase
    {
        // Get preview URL for open graph meta data
        $token = Craft::$app->getTokens()->createPreviewToken([
            'preview/preview', [
                'elementType' => Entry::class,
                'sourceId' => $entry->canonicalId,
                'siteId' => $entry->siteId,
                'draftId' => $entry->draftId,
                'revisionId' => $entry->revisionId,
                'userId' => Craft::$app->getUser()->getId(),
            ],
        ], null);

        $url = UrlHelper::urlWithToken($entry->getUrl(), $token);

        return OpenGraphHelper::getMetadata($url);
    }

    /**
     * Returns a base query for getting shares
     *
     * @return Query
     */
    public function getShareBaseQuery(): Query
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
