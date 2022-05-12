<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Helper;

use craft\helpers\Html;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;

/**
 * Element Index related helpers
 *
 * @package Gewerk\SocialMediaConnect\Helper
 */
class ElementIndexHelper
{
    /**
     * Renders a provider for an element index
     *
     * @param ProviderInterface $provider
     * @return string
     */
    public static function provider(ProviderInterface $provider): string
    {
        $providersService = SocialMediaConnect::$plugin->getProviders();

        return '<div class="smc-provider-label">' .
            '<span class="smc-provider-label__icon" aria-hidden="true">' .
            $providersService->getProviderIconSvg($provider) .
            '</span><span class="smc-provider-label__label">' .
            Html::encode($provider->getName()) .
            '</span></div>';
    }
}
