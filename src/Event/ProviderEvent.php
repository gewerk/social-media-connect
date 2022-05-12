<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Event;

use yii\base\Event;

/**
 * List of providers event
 *
 * @package Gewerk\SocialMediaConnect\Event
 */
class ProviderEvent extends Event
{
    /**
     * @var string[] List of registered providers.
     */
    public $providers = [];

    /**
     * @var bool Is provider newly created?
     */
    public $isNew;
}
