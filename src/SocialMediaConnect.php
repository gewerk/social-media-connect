<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect;

use Craft;
use craft\base\Plugin;
use craft\db\MigrationManager;
use craft\elements\Entry;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\i18n\PhpMessageSource;
use craft\services\Drafts;
use craft\services\ProjectConfig;
use craft\web\Controller;
use craft\web\Response;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use Gewerk\SocialMediaConnect\Hooks\EntryHooks;
use Gewerk\SocialMediaConnect\Model\Settings;
use Gewerk\SocialMediaConnect\Plugin\ComponentTrait;
use Gewerk\SocialMediaConnect\Service\ProvidersService;
use Gewerk\SocialMediaConnect\Twig\Variable\SocialMediaConnectVariable;
use yii\base\Event;

/**
 * Inits the plugin and acts as service locator
 *
 * @package Gewerk\SocialMediaConnect
 */
class SocialMediaConnect extends Plugin
{
    use ComponentTrait;

    /**
     * Current plugin instance
     *
     * @var self
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $schemaVersion = '0.1.0';

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public $hasCpSection = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Save current instance
        self::$plugin = $this;

        // Set controller namespaces
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'Gewerk\\SocialMediaConnect\\Console\\Controller';
        } else {
            $this->controllerNamespace = 'Gewerk\\SocialMediaConnect\\Controller';
        }

        // Set alias
        Craft::setAlias('@social-media-connect', $this->getRootPath());

        // Load translations
        Craft::$app->getI18n()->translations['social-media-connect'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en',
            'basePath' => '@social-media-connect/resources/translations',
            'forceTranslation' => true,
            'allowOverrides' => true,
        ];

        // Base template directory
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $e) {
            $e->roots['social-media-connect'] = Craft::getAlias('@social-media-connect/resources/templates');
        });

        // Register control panel routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules['social-media-connect'] = ['template' => 'social-media-connect/posts/index'];
            $event->rules['social-media-connect/accounts'] = ['template' => 'social-media-connect/accounts/index'];
            $event->rules['social-media-connect/accounts/callback'] = 'social-media-connect/accounts/callback';

            $settingsPrefix = 'settings/plugins/social-media-connect';
            $event->rules["{$settingsPrefix}/providers"] = 'social-media-connect/providers/index';
            $event->rules["{$settingsPrefix}/providers/new"] = 'social-media-connect/providers/edit';
            $event->rules["{$settingsPrefix}/providers/edit/<providerId:\d+>"] = 'social-media-connect/providers/edit';
        });

        // Register Twig variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            /** @var CraftVariable $variables */
            $variables = $event->sender;
            $variables->set('socialMediaConnect', SocialMediaConnectVariable::class);
        });

        // Handle plugin registrations
        $this->registerComponents();
        $this->registerProjectConfigListeners();

        // Render the posting interface
        Craft::$app->getView()->hook(
            'cp.entries.edit',
            [EntryHooks::class, 'renderComposeShare']
        );

        // Render the entry share counter
        Craft::$app->getView()->hook(
            'cp.entries.edit.meta',
            [EntryHooks::class, 'renderEntryShareCounter']
        );

        // Move draft shares before merging draft with its canonical element
        Event::on(
            Drafts::class,
            Drafts::EVENT_BEFORE_APPLY_DRAFT,
            [EntryHooks::class, 'moveDraftShares']
        );

        // Push share job to queue on publication
        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            [EntryHooks::class, 'submitShareJob']
        );
    }

    /**
     * Returns the plugin root path
     *
     * @return string
     */
    public function getRootPath()
    {
        return dirname(dirname(__FILE__));
    }

    /**
     * Returns the plugin assets path
     *
     * @return string
     */
    public function getAssetsPath()
    {
        return $this->getRootPath() . DIRECTORY_SEPARATOR . 'assets';
    }

    /**
     * @inheritdoc
     */
    public function getMigrator(): MigrationManager
    {
        /** @var MigrationManager */
        $migrationManager = $this->get('migrator');
        $migrationManager->migrationPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Migration';
        $migrationManager->migrationNamespace = 'Gewerk\\SocialMediaConnect\\Migration';

        return $migrationManager;
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): array
    {
        // First level
        $navigation = parent::getCpNavItem();
        $navigation['label'] = $this->getSettings()->pluginName;
        $navigation['subnav'] = [];

        // Second level
        $navigation['subnav']['posts'] = [
            'label' => Craft::t('social-media-connect', 'Posts'),
            'url' => 'social-media-connect',
        ];

        $navigation['subnav']['accounts'] = [
            'label' => Craft::t('social-media-connect', 'Accounts'),
            'url' => 'social-media-connect/accounts',
        ];

        return $navigation;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): Response
    {
        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        return $controller->renderTemplate('social-media-connect/settings/index', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function createInstallMigration()
    {
        return new Migration\InstallMigration();
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Registers all project config event listeners
     *
     * @return void
     */
    private function registerProjectConfigListeners()
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $providersService = $this->getProviders();
        $projectConfigService
            ->onAdd(ProvidersService::CONFIG_KEY . '.{uid}', [$providersService, 'handleChangedProjectConfig'])
            ->onUpdate(ProvidersService::CONFIG_KEY . '.{uid}', [$providersService, 'handleChangedProjectConfig'])
            ->onRemove(ProvidersService::CONFIG_KEY . '.{uid}', [$providersService, 'handleDeletedProjectConfig']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function (RebuildConfigEvent $event) {
            $event->config['socialMediaConnect'] = [
                'providers' => $this->getProviders()->createAllProviderConfiguration(),
            ];
        });
    }
}
