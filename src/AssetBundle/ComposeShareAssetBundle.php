<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\AssetBundle;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;
use craft\web\View;

/**
 * Asset bundle for loading the Vue component with style for the posting
 * interface
 *
 * @package Gewerk\SocialMediaConnect\AssetBundle
 */
class ComposeShareAssetBundle extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@social-media-connect/resources/assets/dist';

    /** @inheritdoc */
    public $depends = [
        CpAsset::class,
        VueAsset::class,
        SharedCssAssetBundle::class,
    ];

    /** @inheritdoc */
    public $js = [
        'compose-share.js',
    ];

    /** @inheritdoc */
    public $css = [
        'css/compose-share.css',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        /** @var View $view */
        parent::registerAssetFiles($view);

        // Plugin translations
        $view->registerTranslations('social-media-connect', [
            'Post to Social Media',
            'Post to {account}',
            'Cancel',
            'Successful posted to {account}',
            'Use {account}',
        ]);
    }
}
