<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Model;

use craft\base\Model;
use DateTime;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;

/**
 * Token model
 *
 * @package Gewerk\SocialMediaConnect\Model
 */
class Token extends Model
{
    /**
     * @event Event an event that is triggered after the record is created and populated with query result.
     */
    public const EVENT_AFTER_FIND = 'afterFind';

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
    private $provider;

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
        if ($this->provider === null) {
            $this->provider = SocialMediaConnect::$plugin->getProviders()->getProviderById($this->providerId);
        }

        return $this->provider;
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
     * Returns if token is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->expiryDate) {
            return $this->expiryDate < (new DateTime());
        }

        return false;
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
