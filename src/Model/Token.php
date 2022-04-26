<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Model;

use craft\base\Model;
use DateTime;
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use yii\behaviors\AttributeTypecastBehavior;

class Token extends Model
{
    /**
     * @event Event an event that is triggered after the record is created and populated with query result.
     */
    const EVENT_AFTER_FIND = 'afterFind';

    /**
     * @var int Token ID
     */
    public $id;

    /**
     * @var int Provider ID
     */
    public $providerId;

    /**
     * @var string Identifier or username
     */
    public $identifier;

    /**
     * @var string Token or password
     */
    public $token;

    /**
     * @var string|null Refresh token
     */
    public $refreshToken;

    /**
     * @var string[] Scopes
     */
    public $scopes;

    /**
     * @var DateTime|null Access token expiry date
     */
    public $expiryDate;

    /**
     * @var DateTime|null Record updated on
     */
    public $dateUpdated;

    /**
     * @var DateTime|null Record created on
     */
    public $dateCreated;

    /**
     * @var string UUIDv4 for this record
     */
    public $uid;

    /**
     * @var ProviderInterface
     */
    private $_provider;

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::class,
            'typecastAfterFind' => true,
            'attributeTypes' => [
                'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                'providerId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'identifier' => AttributeTypecastBehavior::TYPE_STRING,
                'token' => AttributeTypecastBehavior::TYPE_STRING,
                'refreshToken' => AttributeTypecastBehavior::TYPE_INTEGER,
                'scopes' => fn ($value) => !is_array($value) ? explode(',', (string) $value) : $value,
                'uid' => AttributeTypecastBehavior::TYPE_STRING,
            ],
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'expiryDate';

        return $attributes;
    }

    /**
     * Returns the provider for this token
     *
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        if ($this->_provider === null) {
            $this->_provider = Plugin::$plugin->getProviders()->getProviderById($this->providerId);
        }

        return $this->_provider;
    }

    /**
     * Triggers afterFind event
     *
     * @return void
     */
    public function afterFind()
    {
        $this->trigger(self::EVENT_AFTER_FIND);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['providerId', 'identifier', 'token'], 'required'];

        return $rules;
    }
}
