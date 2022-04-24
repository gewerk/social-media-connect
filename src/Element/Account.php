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
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use Exception;
use Gewerk\SocialMediaConnect\Element\Query\AccountQuery;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use Gewerk\SocialMediaConnect\Record\Account as AccountRecord;
use yii\base\InvalidConfigException;

class Account extends Element
{
    /**
     * @var int|null ID of the user who connected this account
     */
    public $connectorId;

    /**
     * @var int ID of the oAuth2 token
     */
    public $tokenId;

    /**
     * @var string Account identifier like an ID or handle
     */
    public $identifier;

    /**
     * @var string Account name
     */
    public $name;

    /**
     * @var string|null Account handle
     */
    public $handle;

    /**
     * @var int|null ID of account picture
     */
    public $photoId;

    /**
     * @var DateTime|null Last time the account details were refreshed
     */
    public $lastRefreshedAt = null;

    /**
     * @var array
     */
    public $settings = [];

    /**
     * @var User
     */
    private $_connector;

    /**
     * @var Token
     */
    private $_token;

    /**
     * @var Asset
     */
    private $_photo;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('social-media-connect', 'Social Media Account');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('social-media-connect', 'Social Media Accounts');
    }

    /**
     * @inheritdoc
     * @return AccountQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new AccountQuery(static::class);
    }

    /**
     * Finds one or create a new instance with provided criteria
     *
     * @param array $criteria
     * @return self
     */
    public static function findOneOrCreate($criteria)
    {
        return static::findByCondition($criteria, true) ?? new static($criteria);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (is_string($this->settings)) {
            $this->settings = Json::decodeIfJson($this->settings);
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'lastRefreshedAt';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'provider';
        $names[] = 'photo';
        $names[] = 'connector';

        return $names;
    }

    /**
     * Returns the token used by this account
     *
     * @return Token
     * @throws InvalidConfigException if [[tokenId]] is null or set but invalid
     */
    public function getToken(): Token
    {
        if ($this->_token === null) {
            $this->_token = Plugin::$plugin->getTokens()->getTokenByAccount($this);
        }

        return $this->_token;
    }

    /**
     * Sets the token for this social media account
     *
     * @param Token $token
     */
    public function setToken(Token $token)
    {
        $this->_token = $token;
        $this->tokenId = $token->id;
    }

    /**
     * Returns the provider for this account
     *
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        return $this->getToken()->getProvider();
    }

    /**
     * Returns if this account can be used to compose shares
     *
     * @return bool
     */
    public function getSupportsComposing(): bool
    {
        $provider = $this->getProvider();

        return $provider instanceof ComposingCapabilityInterface && $provider->supportsComposing($this);
    }

    /**
     * @inheritdoc
     */
    public function getHasRoundedThumb(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getIsDeletable(): bool
    {
        return true;
    }

    /**
     * Returns the account connector
     *
     * @return User|null
     */
    public function getConnector(): ?User
    {
        if ($this->_connector === null) {
            if ($this->connectorId === null) {
                return null;
            }

            $this->_connector = Craft::$app->getUsers()->getUserById($this->connectorId);
        }

        return $this->_connector;
    }

    /**
     * Sets the connector of this account
     *
     * @param User $connector
     */
    public function setConnector(User $connector = null)
    {
        $this->_connector = $connector;
        $this->connectorId = $connector->id ?? null;
    }

    /**
     * Returns the account photo.
     *
     * @return Asset|null
     */
    public function getPhoto()
    {
        if ($this->_photo === null) {
            if (!$this->photoId) {
                return null;
            }

            $this->_photo = Craft::$app->getAssets()->getAssetById($this->photoId) ?? false;
        }

        return $this->_photo ?: null;
    }

    /**
     * Sets the account photo.
     *
     * @param Asset|null $photo
     */
    public function setPhoto(Asset $photo = null)
    {
        $this->_photo = $photo;
        $this->photoId = $photo->id ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
    {
        $photo = $this->getPhoto();

        if ($photo) {
            return Craft::$app->getAssets()->getThumbUrl($photo, $size, $size, false);
        }

        return Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/cp/dist', true, 'images/user.svg');
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        // Get the entry record
        if (!$isNew) {
            $record = AccountRecord::findOne($this->id);

            if (!$record) {
                throw new Exception("Invalid entry ID: {$this->id}");
            }
        } else {
            $record = new AccountRecord();
            $record->id = (int) $this->id;
        }

        // Set attributes
        $record->connectorId = $this->connectorId;
        $record->tokenId = $this->tokenId;
        $record->identifier = $this->identifier;
        $record->name = StringHelper::encodeMb4($this->name);
        $record->handle = $this->handle;
        $record->settings = Db::prepareValueForDb($this->settings);
        $record->lastRefreshedAt = Db::prepareDateForDb($this->lastRefreshedAt);

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

        // Add sources for all providers
        foreach (Plugin::$plugin->getProviders()->getAllProviders() as $provider) {
            $sources[] = [
                'key' => $provider->getHandle(),
                'label' => $provider->getName(),
                'criteria' => [
                    'provider' => $provider->getHandle(),
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
            'handle' => ['label' => Craft::t('social-media-connect', 'Handle')],
            'name' => ['label' => Craft::t('social-media-connect', 'Name')],
            'provider' => ['label' => Craft::t('social-media-connect', 'Provider')],
            'connector' => ['label' => Craft::t('social-media-connect', 'Connected by')],
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = array_filter([
            'handle', 'name', $source === '*' ? 'provider' : null, 'connector',
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
                $provider = $this->getProvider();
                $providersService = Plugin::$plugin->getProviders();

                return '<div class="smc-provider-label">' .
                    '<span class="smc-provider-label__icon" aria-hidden="true">' .
                    $providersService->getProviderIconSvg($provider) .
                    '</span><span class="smc-provider-label__label">' .
                    Html::encode($provider->getName()) .
                    '</span></div>';

            case 'name':
                return html_entity_decode($this->name);

            case 'handle':
                return $this->handle;

            case 'connector':
                return $this->getConnector() ? Cp::elementHtml($this->getConnector()) : '';
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'handle' => Craft::t('social-media-connect', 'Handle'),
            'name' => Craft::t('social-media-connect', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['handle', 'name'];
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
     * Returns the string representation of the element.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->handle !== null && $this->handle !== '') {
            return (string) $this->handle;
        }

        return (string) $this->id ?: static::class;
    }
}
