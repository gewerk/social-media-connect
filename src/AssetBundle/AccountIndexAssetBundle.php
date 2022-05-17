<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\AssetBundle;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use Gewerk\SocialMediaConnect\SocialMediaConnect;

/**
 * Asset bundle for the element index helper for accounts
 *
 * @package Gewerk\SocialMediaConnect\AssetBundle
 */
class AccountIndexAssetBundle extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@social-media-connect/resources/assets/dist';

    /** @inheritdoc */
    public $depends = [
        CpAsset::class,
        SharedCssAssetBundle::class,
    ];

    /** @inheritdoc */
    public $js = [
        'account-index.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        /** @var View $view */
        parent::registerAssetFiles($view);

        // Get providers
        $providersService = SocialMediaConnect::$plugin->getProviders();
        $view->registerJsVar('socialMediaConnectProviders', array_map(fn (ProviderInterface $provider) => [
            'handle' => $provider->handle,
            'name' => $provider->name,
            'icon' => $providersService->getProviderIconSvg($provider),
        ], $providersService->getAllProviders()));

        // Plugin translations
        $view->registerTranslations('social-media-connect', [
            'Add account',
            'Add {provider} account',
        ]);
    }
}
