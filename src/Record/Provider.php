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
 * @property string $name Provider name
 * @property string $handle Handle
 * @property string $type Provider type
 * @property bool $enabled Is provider enabled
 * @property string $settings Settings
 * @property int $sortOrder Sort order
 * @property DateTime|null $dateUpdated Record updated on
 * @property DateTime|null $dateCreated Record created on
 * @property string $uid UUIDv4 for this record
 */
class Provider extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%social_media_connect_providers}}';
    }
}
