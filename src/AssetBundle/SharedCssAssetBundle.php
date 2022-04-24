<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\AssetBundle;

use craft\web\AssetBundle;

/**
 * Asset bundle for shared css in this plugin
 *
 * @package Gewerk\SocialMediaConnect\AssetBundle
 */
class SharedCssAssetBundle extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@social-media-connect/resources/assets/dist';

    /** @inheritdoc */
    public $css = [
        'css/social-media-connect.css',
    ];
}
