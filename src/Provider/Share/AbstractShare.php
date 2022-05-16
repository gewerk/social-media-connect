<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\Share;

use Craft;
use craft\base\SavableComponent;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\validators\DateTimeValidator;
use DateTime;
use Gewerk\SocialMediaConnect\Element\Account;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * Abstract base class for composed social media posts
 *
 * @package Gewerk\SocialMediaConnect\Model
 */
abstract class AbstractShare extends SavableComponent
{
    /**
     * @var int Entry ID
     */
    public $entryId;

    /**
     * @var int Site ID
     */
    public $siteId;

    /**
     * @var int Account ID
     */
    public $accountId;

    /**
     * @var bool Post this share with entry publish?
     */
    public $publishWithEntry = true;

    /**
     * @var DateTime|null When to publish
     */
    public $postAt = null;

    /**
     * @var DateTime|null Posted at
     */
    public $postedAt = null;

    /**
     * @var bool|null Successful posted
     */
    public $success = null;

    /**
     * @var mixed|null Response
     */
    public $response = null;

    /**
     * @var string|null Share URL
     */
    public $postUrl = null;

    /**
     * @var string|null UID
     */
    public $uid = null;

    /**
     * @var Entry|null
     */
    private $entry = null;

    /**
     * @var Account|null
     */
    private $account = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        foreach ($this->datetimeAttributes() as $attribute) {
            if ($this->$attribute !== null) {
                $this->$attribute = DateTimeHelper::toDateTime($this->$attribute);
            }
        }

        $this->typecastAttributes();
    }

    /**
     * Sets entry for this share
     *
     * @param Entry $entry
     * @return void
     */
    public function setEntry(Entry $entry): void
    {
        $this->entryId = $entry->id;
        $this->siteId = $entry->siteId;
        $this->entry = $entry;
    }

    /**
     * Returns entry for this share
     *
     * @return Entry
     */
    public function getEntry(): Entry
    {
        if ($this->entry === null) {
            $this->entry = Craft::$app->getElements()->getElementById(
                $this->entryId,
                Entry::class,
                $this->siteId
            );
        }

        return $this->entry;
    }

    /**
     * Sets account for this share
     *
     * @param Account $account
     * @return void
     */
    public function setAccount(Account $account): void
    {
        $this->accountId = $account->id;
        $this->account = $account;
    }

    /**
     * Returns account for this share
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        if ($this->account === null) {
            $this->account = Craft::$app->getElements()->getElementById(
                $this->accountId,
                Account::class,
                $this->siteId
            );
        }

        return $this->account;
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::class,
            'attributeTypes' => [
                'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                'entryId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'siteId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'accountId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'publishWithEntry' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'success' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'response' => fn ($value) => Json::decodeIfJson($value),
                'postUrl' => AttributeTypecastBehavior::TYPE_STRING,
                'uid' => AttributeTypecastBehavior::TYPE_STRING,
            ],
        ];

        return $behaviors;
    }

    /**
     * Returns the names of any attributes that should hold [[\DateTime]] values.
     *
     * @return string[]
     */
    public function datetimeAttributes(): array
    {
        $attributes = [];
        $attributes[] = 'postAt';
        $attributes[] = 'postedAt';
        $attributes[] = 'dateCreated';
        $attributes[] = 'dateUpdated';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publishWithEntry'], 'required'];
        $rules[] = [['postAt'], DateTimeValidator::class];

        return $rules;
    }
}
