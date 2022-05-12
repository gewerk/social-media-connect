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
 * @property bool $publishWithEntry Should the share published with the entry
 * @property DateTime|null $postAt Planed date for publication
 * @property string $settings Share settings
 * @property bool|null $success Successful posted
 * @property string|null $response Response
 * @property string|null $postUrl URL of the posted share
 * @property DateTime|null $postedAt Publication date of the share
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
