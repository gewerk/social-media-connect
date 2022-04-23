<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Record;

use craft\db\ActiveRecord;

/**
 * @property int $id ID
 * @property string $entryId Entry ID
 * @property string $siteId Element ID
 * @property string $accountId Provider ID
 * @property bool $publishWithEntry Post with publish
 * @property DateTime|null $postAt Post share at
 * @property string $settings Settings
 * @property bool|null $success Success
 * @property string|null $response Response
 * @property string|null $postUrl Post url
 * @property DateTime|null $postedAt Post share at
 * @property DateTime|null $dateUpdated Record updated on
 * @property DateTime|null $dateCreated Record created on
 * @property string $uid UUIDv4 for this record
 */
class Share extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%social_media_connect_shares}}';
    }
}
