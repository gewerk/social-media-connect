<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\Share;

use craft\base\SavableComponent;
use craft\elements\Entry;
use craft\validators\DateTimeValidator;
use DateTime;
use Gewerk\SocialMediaConnect\Element\Account;

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
     * @var bool Successful posted
     */
    public $success = false;

    /**
     * @var array|null Response
     */
    public $response = null;

    /**
     * @var string|null Share URL
     */
    public $postUrl = null;

    /**
     * @var Entry|null
     */
    private $_entry = null;

    /**
     * @var Account|null
     */
    private $_account = null;

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
        $this->_entry = $entry;
    }

    /**
     * Returns entry for this share
     *
     * @return Entry
     */
    public function getEntry(): Entry
    {
        if ($this->_entry === null) {
            $this->_entry = Entry::findOne([
                'id' => $this->entryId,
                'siteId' => $this->siteId,
            ]);
        }

        return $this->_entry;
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
        $this->_account = $account;
    }

    /**
     * Returns account for this share
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        if ($this->_account === null) {
            $this->_account = Account::findOne([
                'id' => $this->accountId,
            ]);
        }

        return $this->_account;
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
