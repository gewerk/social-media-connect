<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Event;

use yii\base\Event;

class RegisterProvidersEvent extends Event
{
    /**
     * @var string[] List of registered providers.
     */
    public $providers = [];
}
