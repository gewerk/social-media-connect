<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Record;

use craft\db\ActiveRecord;

/**
 * @property int $id Token ID
 * @property string $providerId Provider ID
 * @property string $identifier Identifier or username
 * @property string $token Token or password
 * @property string|null $refreshToken Refresh token
 * @property string $scopes Scopes
 * @property DateTime|null $expiryDate Access token expiry date
 * @property DateTime|null $dateUpdated Record updated on
 * @property DateTime|null $dateCreated Record created on
 * @property string $uid UUIDv4 for this record
 */
class Token extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%social_media_connect_tokens}}';
    }
}
