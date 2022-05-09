<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Record;

use craft\db\ActiveRecord;

/**
 * @property int $id Account ID
 * @property int $tokenId Token ID
 * @property int|null $connectorId ID of user which connected this account
 * @property string $identifier Identifier
 * @property string $name Account name
 * @property string $handle Account handle
 * @property string $settings Settings
 * @property DateTime|null $lastRefreshedAt Last refreshed at
 * @property DateTime|null $dateUpdated Record updated on
 * @property DateTime|null $dateCreated Record created on
 * @property string $uid UUIDv4 for this record
 */
class Account extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%social_media_connect_accounts}}';
    }
}
