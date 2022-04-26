<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Service;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use Gewerk\SocialMediaConnect\Event\ProviderEvent;
use Gewerk\SocialMediaConnect\Event\RegisterProvidersEvent;
use Gewerk\SocialMediaConnect\Provider;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use Gewerk\SocialMediaConnect\Record\Provider as ProviderRecord;
use Throwable;

class ProvidersService extends Component
{
    const CONFIG_KEY = 'socialMediaConnect.providers';
    const EVENT_REGISTER_PROVIDERS = 'registerProviders';
    const EVENT_BEFORE_SAVE_PROVIDER = 'beforeSaveProvider';
    const EVENT_AFTER_SAVE_PROVIDER = 'afterSaveProvider';
    const EVENT_BEFORE_DELETE_PROVIDER = 'beforeDeleteProvider';
    const EVENT_AFTER_DELETE_PROVIDER = 'afterDeleteProvider';
    const EVENT_BEFORE_APPLY_PROVIDER_DELETE = 'beforeApplyProviderDelete';

    /**
     * @var ProviderInterface[]
     */
    private $_providers = null;

    /**
     * Returns all providers
     *
     * @return ProviderInterface[]
     */
    public function getAllProviders(): array
    {
        if ($this->_providers !== null) {
            return $this->_providers;
        }

        $this->_providers = [];

        $providerRecords = (new Query())
            ->select(['id', 'name', 'handle', 'type', 'enabled', 'sortOrder', 'settings', 'dateCreated', 'dateUpdated', 'uid'])
            ->from(ProviderRecord::tableName())
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        foreach ($providerRecords as $providerRecord) {
            $this->_providers[] = $this->createProvider($providerRecord);
        }

        return $this->_providers;
    }

    /**
     * Returns a provider by ID
     *
     * @param int $id
     * @return ProviderInterface|null
     */
    public function getProviderById(int $id): ?ProviderInterface
    {
        return ArrayHelper::firstWhere($this->getAllProviders(), 'id', $id);
    }

    /**
     * Returns a provider by UID
     *
     * @param int $uid
     * @return ProviderInterface|null
     */
    public function getProviderByUid(string $uid)
    {
        return ArrayHelper::firstWhere($this->getAllProviders(), 'uid', $uid);
    }

    /**
     * Returns a provider by handle
     *
     * @param int $handle
     * @return ProviderInterface|null
     */
    public function getProviderByHandle(string $handle)
    {
        return ArrayHelper::firstWhere($this->getAllProviders(), 'handle', $handle);
    }

    /**
     * Gets all registered provider types
     *
     * @return string[]
     */
    public function getAllProviderTypes()
    {
        $providers = [
            Provider\FacebookPagesProvider::class,
            Provider\InstagramProvider::class,
            Provider\TwitterProvider::class,
        ];

        $event = new RegisterProvidersEvent([
            'providers' => $providers,
        ]);

        $this->trigger(self::EVENT_REGISTER_PROVIDERS, $event);

        return $event->providers;
    }

    /**
     * Saves a provider.
     *
     * @param ProviderInterface $provider
     * @param bool $runValidation
     * @return bool
     */
    public function saveProvider(ProviderInterface $provider, bool $runValidation = true): bool
    {
        $isNew = $provider->getIsNew();

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PROVIDER)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PROVIDER, new ProviderEvent([
                'provider' => $provider,
                'isNew' => $isNew
            ]));
        }

        if (!$provider->beforeSave($isNew)) {
            return false;
        }

        if ($runValidation && !$provider->validate()) {
            Craft::info('Provider not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($isNew) {
            $provider->uid = StringHelper::UUID();

            $count = (new Query())->from(ProviderRecord::tableName())->max('[[sortOrder]]');
            $provider->sortOrder = $count + 1;
        } elseif (!$provider->uid) {
            $provider->uid = Db::uidById(ProviderRecord::tableName(), $provider->id);
        }

        Craft::$app->getProjectConfig()->set(
            self::CONFIG_KEY . '.' . $provider->uid,
            $this->createProviderConfiguration($provider),
            "Save the “{$provider->handle}” provider"
        );

        if ($isNew) {
            $provider->id = Db::idByUid(ProviderRecord::tableName(), $provider->uid);
        }

        return true;
    }

    /**
     * Deletes a provider.
     *
     * @param ProviderInterface $provider
     * @return bool
     */
    public function deleteProvider(ProviderInterface $provider): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PROVIDER)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PROVIDER, new ProviderEvent([
                'provider' => $provider
            ]));
        }

        if (!$provider->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(
            self::CONFIG_KEY . '.' . $provider->uid,
            "Delete the “{$provider->handle}” provider"
        );

        return true;
    }

    /**
     * Reorders providers
     *
     * @param array $providerIds
     * @return bool
     */
    public function reorderProviders(array $providerIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(ProviderRecord::tableName(), $providerIds);

        foreach ($providerIds as $providerOrder => $providerId) {
            if (!empty($uidsByIds[$providerId])) {
                $providerUid = $uidsByIds[$providerId];
                $projectConfig->set(
                    self::CONFIG_KEY . '.' . $providerUid . '.sortOrder',
                    $providerOrder + 1,
                    'Reorder providers'
                );
            }
        }

        return true;
    }

    /**
     * Creates an provider with an specific configurations. Missing providers
     * will be replaced with a placeholder.
     *
     * @param array $config
     * @return ProviderInterface
     */
    public function createProvider(array $config): ProviderInterface
    {
        try {
            $provider = ComponentHelper::createComponent($config, ProviderInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];

            unset($config['type']);

            $provider = new Provider\MissingProvider($config);
        }

        return $provider;
    }

    /**
     * Returns the project configuration for a provider.
     *
     * @param ProviderInterface $provider
     * @return array
     */
    public function createProviderConfiguration(ProviderInterface $provider): array
    {
        $config = [
            'name' => $provider->name,
            'handle' => $provider->handle,
            'type' => get_class($provider),
            'enabled' => (bool) $provider->enabled,
            'sortOrder' => (int) $provider->sortOrder,
            'settings' => ProjectConfig::packAssociativeArrays($provider->getSettings()),
        ];

        return $config;
    }

    /**
     * Returns project configuration for all providers
     *
     * @return array
     */
    public function createAllProviderConfiguration(): array
    {
        $data = [];

        foreach ($this->getAllProviders() as $provider) {
            $data[$provider->uid] = $this->createProviderConfiguration($provider);
        }

        return $data;
    }

    /**
     * Handles changes in project configuration
     *
     * @param ConfigEvent $configEvent
     * @return void
     */
    public function handleChangedProjectConfig(ConfigEvent $configEvent)
    {
        $providerUid = $configEvent->tokenMatches[0];
        $data = $configEvent->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $providerRecord = ProviderRecord::findOne([
                'uid' => $providerUid,
            ]) ?? new ProviderRecord([
                'uid' => $providerUid,
            ]);

            $isNew = $providerRecord->getIsNewRecord();

            $providerRecord->name = $data['name'];
            $providerRecord->handle = $data['handle'];
            $providerRecord->type = $data['type'];
            $providerRecord->enabled = $data['enabled'];
            $providerRecord->sortOrder = $data['sortOrder'];
            $providerRecord->settings = ProjectConfig::unpackAssociativeArrays($data['settings'] ?? []);

            $providerRecord->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_providers = null;

        $provider = $this->getProviderById($providerRecord->id);
        $provider->afterSave($isNew);

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PROVIDER)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PROVIDER, new ProviderEvent([
                'provider' => $provider,
                'isNew' => $isNew,
            ]));
        }
    }

    /**
     * Handles deletes in project configuration
     *
     * @param ConfigEvent $event
     * @return void
     */
    public function handleDeletedProjectConfig(ConfigEvent $event)
    {
        $providerUid = $event->tokenMatches[0];
        $providerRecord = ProviderRecord::findOne([
            'uid' => $providerUid,
        ]);

        if ($providerRecord->getIsNewRecord()) {
            return;
        }

        $provider = $this->getProviderById($providerRecord->id);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_PROVIDER_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_PROVIDER_DELETE, new ProviderEvent([
                'provider' => $provider,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $provider->beforeApplyDelete();

            Db::delete(
                ProviderRecord::tableName(),
                ['id' => $providerRecord->id]
            );

            $provider->afterDelete();
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_providers = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_PROVIDER)) {
            $this->trigger(self::EVENT_AFTER_DELETE_PROVIDER, new ProviderEvent([
                'provider' => $provider
            ]));
        }
    }

    /**
     * Returns the provider icon as svg string
     *
     * @param ProviderInterface $provider
     * @return string
     */
    public function getProviderIconSvg(ProviderInterface $provider): string
    {
        return ComponentHelper::iconSvg($provider::icon(), $provider::displayName());
    }
}
