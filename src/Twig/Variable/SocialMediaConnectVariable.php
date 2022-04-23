<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Twig\Variable;

use Gewerk\SocialMediaConnect\Plugin;

class SocialMediaConnectVariable
{
    public function getProviders()
    {
        return Plugin::$plugin->getProviders()->getAllProviders();
    }
}
