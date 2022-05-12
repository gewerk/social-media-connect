<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Record;

use craft\db\ActiveRecord;

/**
 * @property int $id Post ID
 * @property int $accountId Account ID
 * @property string $identifier Identifier
 * @property DateTime|null $postedAt Posted at
 * @property string|null $url URL
 * @property string $type Type
 * @property string $payload Payload
 * @property DateTime|null $dateUpdated Record updated on
 * @property DateTime|null $dateCreated Record created on
 * @property string $uid UUIDv4 for this record
 */
class Post extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%social_media_connect_posts}}';
    }
}
