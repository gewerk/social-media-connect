<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Event;

use Gewerk\SocialMediaConnect\Provider\OAuth2\ProviderInterface;
use yii\base\Event;

/**
 * This event is used if a provider is saved
 *
 * @package Gewerk\SocialMediaConnect\Event
 */
class ProviderEvent extends Event
{
    /**
     * @var ProviderInterface Saved provider
     */
    public $provider;

    /**
     * @var bool Is provider newly created?
     */
    public $isNew = false;
}
