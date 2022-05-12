<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Element;

use Craft;
use craft\base\Element;
use craft\elements\actions as Actions;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use Gewerk\SocialMediaConnect\Element\Query\PostQuery;
use Gewerk\SocialMediaConnect\Helper\ElementIndexHelper;
use Gewerk\SocialMediaConnect\Provider\PostPayload\AbstractPostPayload;
use Gewerk\SocialMediaConnect\Record\Post as PostRecord;
use yii\base\Exception;
use yii\db\Expression;

/**
 * A post element
 *
 * @package Gewerk\SocialMediaConnect\Element
 */
class Post extends Element
{
    /**
     * @var string
     */
    public $identifier;

    /**
     * @var int
     */
    public $accountId;

    /**
     * @var array
     */
    public $payload;

    /**
     * @var string
     */
    public $type;

    /**
     * @var DateTime|null Posted at
     */
    public $postedAt = null;

    /**
     * @var string|null Post URL
     */
    public $url = null;

    /**
     * @var Account|null
     */
    private $account = null;

    /**
     * @var AbstractPostPayload|null
     */
    private $postPayload = null;

    /**
     * @inheritdoc
     * @return PostQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new PostQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'postedAt';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getIsDeletable(): bool
    {
        return true;
    }

    /**
     * Sets account for this post
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
     * Returns account for this post
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        if ($this->account === null) {
            $this->account = Craft::$app->getElements()->getElementById(
                $this->accountId,
                Account::class
            );
        }

        return $this->account;
    }

    /**
     * Sets payload for this post
     *
     * @param AbstractPostPayload $payload
     * @return void
     */
    public function setPayload(AbstractPostPayload $payload): void
    {
        $this->postPayload = $payload;
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns payload for this post
     *
     * @return AbstractPostPayload
     */
    public function getPayload(): AbstractPostPayload
    {
        if ($this->postPayload === null) {
            $this->postPayload = ComponentHelper::createComponent(
                [
                    'type' => $this->type,
                    'settings' => $this->payload,
                ],
                AbstractPostPayload::class
            );
        }

        return $this->postPayload;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        // Get the entry record
        if (!$isNew) {
            $record = PostRecord::findOne($this->id);

            if (!$record) {
                throw new Exception("Invalid entry ID: {$this->id}");
            }
        } else {
            $record = new PostRecord();
            $record->id = (int) $this->id;
        }

        // Set attributes
        $record->accountId = $this->accountId;
        $record->identifier = $this->identifier;
        $record->url = $this->url;
        $record->type = $this->type;
        $record->payload = Db::prepareValueForDb($this->postPayload->getSettings());
        $record->postedAt = Db::prepareDateForDb($this->postedAt);

        // Save record
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        // Default source
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('social-media-connect', 'All Accounts'),
                'criteria' => [],
            ],
        ];

        // Get accounts which support pulling
        /** @var Account[] */
        $accounts = ArrayHelper::where(
            Account::findAll(),
            'supportsPulling',
            true
        );

        // Add sources for all providers
        foreach ($accounts as $account) {
            $sources[] = [
                'key' => $account->uid,
                'label' => $account->name,
                'criteria' => [
                    'accountId' => $account->id,
                ],
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'label' => ['label' => ''],
            'post' => ['label' => Craft::t('social-media-connect', 'Post')],
            'account' => ['label' => Craft::t('social-media-connect', 'Account')],
            'provider' => ['label' => Craft::t('social-media-connect', 'Provider')],
            'postedAt' => ['label' => Craft::t('social-media-connect', 'Posted At')],
            'uri' => ['label' => Craft::t('social-media-connect', 'URL')],
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = array_filter([
            'post', $source === '*' ? 'account' : null, 'provider', 'postedAt', 'uri',
        ]);

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'provider':
                $provider = $this->getAccount()->getProvider();

                return ElementIndexHelper::provider($provider);

            case 'post':
                return $this->getAccount()->getProvider()->getPostAttributeHtml($this);

            case 'account':
                return Cp::elementHtml($this->getAccount());
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'postedAt' => [
                'label' => Craft::t('app', 'Posted At'),
                'orderBy' => function (int $dir) {
                    if ($dir === SORT_ASC) {
                        if (Craft::$app->getDb()->getIsMysql()) {
                            return new Expression('[[postedAt]] IS NOT NULL DESC, [[postedAt]] ASC');
                        } else {
                            return new Expression('[[postedAt]] ASC NULLS LAST');
                        }
                    }

                    if (Craft::$app->getDb()->getIsMysql()) {
                        return new Expression('[[postedAt]] IS NULL DESC, [[postedAt]] DESC');
                    } else {
                        return new Expression('[[postedAt]] DESC NULLS FIRST');
                    }
                },
                'attribute' => 'postedAt',
                'defaultDir' => 'asc',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];
        $elementsService = Craft::$app->getElements();

        $actions[] = $elementsService->createAction([
            'type' => Actions\Edit::class,
            'label' => Craft::t('app', 'Edit entry'),
        ]);

        $actions[] = [
            'type' => Actions\Delete::class,
        ];

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        return '';
    }
}
